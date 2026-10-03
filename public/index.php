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
$level = (string) ($_POST['level'] ?? $_GET['level'] ?? $_SESSION['level'] ?? 'trung_cap');
if (!isset($levels[$level])) {
    $level = 'trung_cap';
}

if ($action === 'preview') {
    $previewLevel = (string) ($_GET['level'] ?? '');
    $previewNumber = filter_var($_GET['form'] ?? null, FILTER_VALIDATE_INT);
    session_write_close();

    try {
        (new PdfDossierExporter())->outputBlankPreview($previewLevel, $previewNumber === false ? 0 : (int) $previewNumber);
    } catch (InvalidArgumentException) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Không tìm thấy biểu mẫu xem trước.');
    } catch (Throwable) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Chưa thể tạo bản xem trước. Vui lòng thử lại hoặc liên hệ quản trị viên.');
    }
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
        } elseif ($action === 'export-form' && is_array($inspection)) {
            $formNumber = filter_var($_POST['form_number'] ?? null, FILTER_VALIDATE_INT);
            if ($formNumber === false) {
                throw new InvalidArgumentException('Vui lòng chọn biểu mẫu cần tải riêng.');
            }
            $mapping = $_SESSION['mapping'] ?? StudentMapper::suggest($inspection['headers']);
            (new PdfDossierExporter())->outputForm($level, (int) $formNumber, $inspection, $mapping);
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
$commonForms = array_filter($forms, static fn (array $form): bool => $form['number'] <= 7);
$levelForms = array_filter($forms, static fn (array $form): bool => $form['number'] > 7);
$canExport = $draftTemplateReady
    && is_array($inspection)
    && isset($inspection['sheet_data'])
    && $mapped['students'] !== []
    && $mapped['errors'] === [];
$e = [Html::class, 'escape'];

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="light">
    <title>AIVANBAN - Tạo hồ sơ đào tạo</title>
    <link rel="stylesheet" href="assets/app.css?v=<?= (string) filemtime(__DIR__ . '/assets/app.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="site-header-inner">
        <a class="brand" href="./" aria-label="AIVANBAN - Trường Trung cấp Miền Tây - Trang chủ">
            <img class="school-logo" src="assets/logo-truong-trung-cap-mien-tay.png" alt="">
            <span class="brand-copy"><span class="brand-name">AIVANBAN</span><span class="brand-school">Trường Trung cấp Miền Tây</span></span>
        </a>
        <a class="template-link" href="?action=download-template">Tải file Excel mẫu</a>
    </div>
</header>

<main class="page-shell">
    <section class="page-intro">
        <h1>Tạo hồ sơ đào tạo</h1>
        <p class="intro-description">Xem mẫu trống, tải Excel lên và xuất bộ hồ sơ PDF.</p>
    </section>

    <?php if ($message !== null): ?>
        <div class="notice" role="status" aria-live="polite"><span class="notice-icon" aria-hidden="true">i</span><?= $e($message) ?></div>
    <?php endif; ?>

    <section class="section-block forms-section" aria-labelledby="forms-title">
        <div class="section-heading forms-heading">
            <div>
                <h2 id="forms-title">Mẫu trống</h2>
                <p><?= count($forms) ?> biểu mẫu cho trình độ <?= $e($levels[$level]) ?>.</p>
            </div>
            <form class="form-level-filter" method="get">
                <label for="catalog-level">Trình độ</label>
                <select id="catalog-level" name="level">
                    <?php foreach ($levels as $value => $label): ?>
                        <option value="<?= $e($value) ?>" <?= $level === $value ? 'selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-secondary" type="submit">Đổi</button>
            </form>
        </div>
        <p class="legal-note">Mẫu trống tham khảo Quyết định 62/2008, chưa xác nhận là mẫu hiện hành hoặc đã được trường phê duyệt. Vui lòng đối chiếu trước khi sử dụng.</p>

        <form class="preview-form" method="get" action="./" target="_blank" rel="noopener">
            <input type="hidden" name="action" value="preview">
            <input type="hidden" name="level" value="<?= $e($level) ?>">
            <label class="field-label" for="form-preview">Chọn biểu mẫu cần xem</label>
            <div class="preview-controls">
                <select id="form-preview" name="form" required>
                    <option value="" selected disabled>Chọn một biểu mẫu</option>
                    <optgroup label="Biểu mẫu theo trình độ">
                        <?php foreach ($levelForms as $form): ?>
                            <?php $formLabel = $form['name'] . (is_array($inspection) && !$form['ready'] ? ' (Thiếu bảng: ' . implode(', ', $form['missing_sheets']) . ')' : ''); ?>
                            <option value="<?= $e($form['number']) ?>"><?= $e($formLabel) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Biểu mẫu chung 1-7">
                        <?php foreach ($commonForms as $form): ?>
                            <?php $formLabel = $form['name'] . (is_array($inspection) && !$form['ready'] ? ' (Thiếu bảng: ' . implode(', ', $form['missing_sheets']) . ')' : ''); ?>
                            <option value="<?= $e($form['number']) ?>"><?= $e($formLabel) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <button class="button button-primary" type="submit">Xem mẫu</button>
            </div>
        </form>
    </section>

    <section class="section-block upload-section" aria-labelledby="upload-title">
        <div class="section-heading">
            <div>
                <h2 id="upload-title">Tải bảng Excel</h2>
                <p>Trình độ đã chọn: <strong><?= $e($levels[$level]) ?></strong></p>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="action" value="inspect">
            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
            <input type="hidden" name="level" value="<?= $e($level) ?>">
            <label class="field-group file-field">
                <span class="field-label">Tệp dữ liệu người học</span>
                <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required aria-describedby="file-help">
                <small id="file-help">XLSX, XLS hoặc CSV. Tối đa 10 MB.</small>
            </label>
            <button class="button button-primary upload-button" type="submit">Đọc và kiểm tra</button>
        </form>
    </section>

    <?php if (is_array($inspection)): ?>
        <section class="section-block data-section" aria-labelledby="data-title">
            <div class="section-heading">
                <div>
                    <h2 id="data-title">Kiểm tra danh sách</h2>
                    <p><?= count($mapped['students']) ?> người học được đọc từ tệp</p>
                </div>
                <span class="result-count <?= $mapped['errors'] === [] ? 'result-ok' : 'result-error' ?>">
                    <?= count($mapped['errors']) === 0 ? 'Không có lỗi' : count($mapped['errors']) . ' mục cần sửa' ?>
                </span>
            </div>
            <?php if ($mapped['errors'] !== []): ?>
                <ul class="errors" aria-label="Các lỗi dữ liệu cần xử lý">
                    <?php foreach (array_slice($mapped['errors'], 0, 30) as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="success"><span aria-hidden="true">&#10003;</span> Các trường bắt buộc hợp lệ, không phát hiện mã người học trùng.</p>
            <?php endif; ?>
            <div class="table-wrap">
                <table>
                    <caption class="visually-hidden">Bản xem trước danh sách người học</caption>
                    <thead><tr><th scope="col">TT</th><th scope="col">Họ và tên</th><th scope="col">Ngày sinh</th><th scope="col">Điện thoại</th><th scope="col">Số hiệu văn bằng</th><th scope="col">Ngày cấp văn bằng</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($mapped['students'], 0, 20) as $student): ?>
                        <tr><td><?= $e($student['student_code']) ?></td><td><?= $e($student['full_name']) ?></td><td><?= $e($student['date_of_birth']) ?></td><td><?= $e($student['phone']) ?></td><td><?= $e($student['diploma_number']) ?></td><td><?= $e($student['diploma_issue_date']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($mapped['students']) > 20): ?>
                <p class="table-note">Đang xem 20 trên <?= count($mapped['students']) ?> người học.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if (is_array($inspection)): ?>
        <section class="export-box" aria-label="Xuất hồ sơ">
            <div>
                <strong>Xuất bộ hồ sơ</strong>
                <p>Tải cả bộ dưới dạng ZIP hoặc chọn một biểu mẫu để tải PDF riêng.</p>
            </div>
            <?php if ($canExport): ?>
                <form class="export-all-form" method="post">
                    <input type="hidden" name="action" value="export">
                    <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="level" value="<?= $e($level) ?>">
                    <button class="button button-primary export-button" type="submit">Tải cả bộ ZIP</button>
                </form>
            <?php else: ?>
                <span class="export-disabled"><?= isset($inspection['sheet_data']) ? 'Hãy xử lý lỗi dữ liệu trước' : 'Vui lòng tải lại tệp Excel' ?></span>
            <?php endif; ?>
        </section>
        <?php if ($canExport): ?>
            <details class="export-single">
                <summary class="button export-single-toggle">
                    <span>Tải riêng từng biểu mẫu</span>
                    <span class="export-single-count"><?= count($forms) ?> biểu mẫu</span>
                </summary>
                <div class="export-form-list" role="group" aria-label="Danh sách biểu mẫu tải riêng">
                    <?php foreach ($forms as $form): ?>
                        <form class="export-form-item" method="post">
                            <input type="hidden" name="action" value="export-form">
                            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="level" value="<?= $e($level) ?>">
                            <input type="hidden" name="form_number" value="<?= (int) $form['number'] ?>">
                            <span>M<?= str_pad((string) $form['number'], 2, '0', STR_PAD_LEFT) ?> · <?= $e($form['name']) ?></span>
                            <button class="button export-form-button" type="submit" aria-label="Tải PDF: <?= $e($form['name']) ?>">Tải PDF</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer class="site-footer"><p>AIVANBAN <span aria-hidden="true">|</span> Kiểm tra nội dung trước khi ký hoặc sử dụng chính thức.</p></footer>
</body>
</html>
