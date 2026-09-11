<?php

declare(strict_types=1);

use AIVANBAN\Services\FormCatalog;
use AIVANBAN\Services\PdfDossierExporter;
use AIVANBAN\Services\SpreadsheetImporter;
use AIVANBAN\Services\StudentMapper;
use AIVANBAN\Services\TemplateGenerator;
use AIVANBAN\Support\Html;

require dirname(__DIR__) . '/vendor/autoload.php';

$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
    http_response_code(500);
    exit('Thư mục phiên làm việc chưa có quyền ghi.');
}
session_save_path($sessionPath);
session_name('aivanban_session');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'use_strict_mode' => true,
]);

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'home');

if ($action === 'download-template') {
    (new TemplateGenerator())->output();
}

$levels = FormCatalog::levels();
$level = (string) ($_POST['level'] ?? $_SESSION['level'] ?? 'trung_cap');
if (!isset($levels[$level])) {
    $level = 'trung_cap';
}

$inspection = $_SESSION['inspection'] ?? null;
$mapping = [];
$mapped = ['students' => [], 'errors' => []];
$message = null;
$draftTemplateReady = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string) $_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    }

    try {
        if ($action === 'inspect') {
            $inspection = (new SpreadsheetImporter())->inspectUpload($_FILES['excel_file'] ?? []);
            $_SESSION['inspection'] = $inspection;
            $_SESSION['level'] = $level;
            $mapping = StudentMapper::suggest($inspection['headers']);
            $_SESSION['mapping'] = $mapping;
            $message = 'Đã đọc file và tự động đề xuất ánh xạ cột.';
        } elseif ($action === 'remap' && is_array($inspection)) {
            foreach (StudentMapper::fields() as $key => $_definition) {
                $raw = (string) ($_POST['mapping'][$key] ?? '');
                $mapping[$key] = $raw === '' ? null : (int) $raw;
            }
            $_SESSION['mapping'] = $mapping;
            $_SESSION['level'] = $level;
            $message = 'Đã cập nhật ánh xạ và kiểm tra lại dữ liệu.';
        } elseif ($action === 'export' && is_array($inspection)) {
            $mapping = $_SESSION['mapping'] ?? StudentMapper::suggest($inspection['headers']);
            (new PdfDossierExporter())->output($level, $inspection, $mapping);
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        if ($action === 'inspect') {
            $inspection = null;
            unset($_SESSION['inspection'], $_SESSION['mapping']);
        }
    }
}

if (is_array($inspection)) {
    $mapping = $mapping ?: ($_SESSION['mapping'] ?? StudentMapper::suggest($inspection['headers']));
    $mapped = StudentMapper::mapRows($inspection['student_rows'], $inspection['headers'], $mapping);
}

$availableSheets = is_array($inspection)
    ? array_keys(array_filter($inspection['sheets'], static fn (int $rowCount): bool => $rowCount > 1))
    : [];
$forms = FormCatalog::package($level, $availableSheets);
$canExport = $draftTemplateReady
    && is_array($inspection)
    && isset($inspection['sheet_data'])
    && $mapped['students'] !== []
    && $mapped['errors'] === [];
$e = [Html::class, 'escape'];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>AIVANBAN — Tạo hồ sơ đào tạo</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="./"><span>AI</span>VANBAN</a>
    <a class="template-link" href="?action=download-template">Tải Excel mẫu</a>
</header>

<main class="page">
    <section class="hero">
        <div>
            <p class="eyebrow">HỒ SƠ ĐÀO TẠO NGHỀ</p>
            <h1>Một lần nhập liệu.<br><em>Đủ bộ hồ sơ.</em></h1>
            <p class="lead">Chọn trình độ, nhập dữ liệu từ Excel và kiểm tra bộ biểu mẫu trước khi tạo tài liệu.</p>
        </div>
        <div class="hero-note">
            <strong>Quy tắc bộ hồ sơ</strong>
            <span>Bộ cơ bản Mẫu 1–7</span>
            <b>+</b>
            <span>Bộ riêng theo trình độ</span>
        </div>
    </section>

    <?php if ($message !== null): ?>
        <div class="notice"><?= $e($message) ?></div>
    <?php endif; ?>

    <section class="panel upload-panel">
        <div class="section-heading">
            <span>01</span>
            <div><h2>Nhập dữ liệu</h2><p>File tối đa 10 MB, định dạng XLSX, XLS hoặc CSV.</p></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="action" value="inspect">
            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
            <label>
                <span>Trình độ đào tạo</span>
                <select name="level">
                    <?php foreach ($levels as $value => $label): ?>
                        <option value="<?= $e($value) ?>" <?= $level === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="file-field">
                <span>File dữ liệu</span>
                <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
            </label>
            <button type="submit">Đọc và kiểm tra file</button>
        </form>
    </section>

    <?php if (is_array($inspection)): ?>
        <section class="panel">
            <div class="section-heading">
                <span>02</span>
                <div><h2>Ánh xạ cột sinh viên</h2><p><?= $e($inspection['filename']) ?> · Sheet <?= $e($inspection['student_sheet']) ?></p></div>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="remap">
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
                <input type="hidden" name="level" value="<?= $e($level) ?>">
                <div class="mapping-grid">
                    <?php foreach (StudentMapper::fields() as $key => $definition): ?>
                        <label>
                            <span><?= $e($definition['label']) ?><?= $definition['required'] ? ' *' : '' ?></span>
                            <select name="mapping[<?= $e($key) ?>]">
                                <option value="">— Chưa ánh xạ —</option>
                                <?php foreach ($inspection['headers'] as $index => $header): ?>
                                    <option value="<?= $index ?>" <?= ($mapping[$key] ?? null) === $index ? 'selected' : '' ?>><?= $e($header ?: 'Cột ' . ($index + 1)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Áp dụng ánh xạ</button>
            </form>
        </section>

        <section class="panel">
            <div class="section-heading">
                <span>03</span>
                <div><h2>Kiểm tra dữ liệu</h2><p><?= count($mapped['students']) ?> người học · <?= count($mapped['errors']) ?> lỗi</p></div>
            </div>
            <?php if ($mapped['errors'] !== []): ?>
                <ul class="errors">
                    <?php foreach (array_slice($mapped['errors'], 0, 30) as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="success">Các trường bắt buộc đã hợp lệ và không có mã sinh viên trùng.</p>
            <?php endif; ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Mã</th><th>Họ và tên</th><th>Ngày sinh</th><th>Lớp</th><th>Khóa</th><th>Nghề/ngành</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($mapped['students'], 0, 20) as $student): ?>
                        <tr><td><?= $e($student['student_code']) ?></td><td><?= $e($student['full_name']) ?></td><td><?= $e($student['date_of_birth']) ?></td><td><?= $e($student['class_code']) ?></td><td><?= $e($student['course']) ?></td><td><?= $e($student['major']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="section-heading">
            <span><?= is_array($inspection) ? '04' : '02' ?></span>
            <div><h2>Danh mục legacy <?= $e(mb_strtolower($levels[$level], 'UTF-8')) ?></h2><p>Tham khảo Quyết định 62/2008; không mặc định là mẫu hiện hành của trường.</p></div>
        </div>
        <div class="form-list">
            <?php foreach ($forms as $form): ?>
                <article class="form-item">
                    <div class="form-number"><?= str_pad((string) $form['number'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div><h3><?= $e($form['name']) ?></h3><p><?= $e($form['scope']) ?></p></div>
                    <?php if (is_array($inspection)): ?>
                        <span class="status <?= $form['ready'] ? 'ready' : 'missing' ?>"><?= $form['ready'] ? 'Có dữ liệu tham khảo' : 'Thiếu dữ liệu: ' . $e(implode(', ', $form['missing_sheets'])) ?></span>
                    <?php else: ?>
                        <span class="status neutral">Chờ file Excel</span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (is_array($inspection)): ?>
            <div class="export-box">
                <div>
                    <strong>Tạo bộ văn bản</strong>
                    <p>Hệ thống tạo riêng từng PDF theo nhóm biểu mẫu và bộ theo trình độ. Các ô chưa có dữ liệu được để trống để bạn kiểm tra trước khi ký.</p>
                </div>
                <?php if ($canExport): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="export">
                        <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="level" value="<?= $e($level) ?>">
                        <button class="export-button" type="submit">Tạo và tải bộ PDF</button>
                    </form>
                <?php else: ?>
                    <span class="export-disabled"><?= isset($inspection['sheet_data']) ? 'Hãy xử lý hết lỗi dữ liệu' : 'Vui lòng tải lại file Excel' ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>Bộ hồ sơ theo dữ liệu đã nhập · Kiểm tra nội dung trước khi ký hoặc sử dụng chính thức</footer>
</body>
</html>
