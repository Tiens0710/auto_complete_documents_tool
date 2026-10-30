<?php

declare(strict_types=1);

use AIVANBAN\Services\CredentialApp;
use AIVANBAN\Services\CredentialPdfGenerator;
use AIVANBAN\Services\CredentialRegistry;
use AIVANBAN\Services\CredentialQr;
use AIVANBAN\Services\SpreadsheetImporter;
use AIVANBAN\Services\StudentMapper;
use AIVANBAN\Support\Html;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$sessionPath = $root . '/storage/sessions/chungchi';
if (!is_dir($sessionPath) && !mkdir($sessionPath, 0775, true) && !is_dir($sessionPath)) {
    http_response_code(500);
    exit('Không thể tạo thư mục phiên làm việc.');
}
if (!is_writable($sessionPath)) {
    http_response_code(500);
    exit('Thư mục phiên làm việc chưa có quyền ghi.');
}
session_save_path($sessionPath);
session_name('aivanban_chungchi_admin');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'use_strict_mode' => true,
    'gc_maxlifetime' => 1800,
]);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'; object-src 'self'");
header('X-Robots-Tag: noindex, nofollow, noarchive');

$escape = [Html::class, 'escape'];
$config = null;
$registry = null;
$pdo = null;
$configError = null;
$message = null;
$errors = [];
$created = [];
$previewData = null;
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

try {
    $config = CredentialApp::config();
    $pdo = CredentialApp::connect($config);
    $registry = new CredentialRegistry($pdo);
} catch (Throwable) {
    $configError = 'Chưa kết nối được cơ sở dữ liệu. Kiểm tra cấu hình MySQL và bảng credentials.';
}

$adminPassword = (string) ($config['admin_password'] ?? '');
$adminPasswordReady = $adminPassword !== ''
    && !str_starts_with($adminPassword, 'CHANGE_THIS')
    && ($registry instanceof CredentialRegistry);
$loggedIn = ($_SESSION['credential_admin'] ?? false) === true;
if ($loggedIn && !($registry instanceof CredentialRegistry)) {
    unset($_SESSION['credential_admin'], $_SESSION['credential_admin_last_activity'], $_SESSION['credential_preview']);
    $loggedIn = false;
}
if ($loggedIn && (int) ($_SESSION['credential_admin_last_activity'] ?? 0) < time() - 1800) {
    unset($_SESSION['credential_admin'], $_SESSION['credential_preview']);
    session_regenerate_id(true);
    $loggedIn = false;
}
if ($loggedIn) {
    $_SESSION['credential_admin_last_activity'] = time();
}

if (isset($_GET['qr']) || isset($_GET['pdf'])) {
    if (!$loggedIn || !($registry instanceof CredentialRegistry)) {
        http_response_code(403);
        exit('Vui lòng đăng nhập để mở tài liệu quản trị.');
    }
    $actionToken = (string) ($_GET['qr'] ?? $_GET['pdf'] ?? '');
    if (preg_match('/^[a-f0-9]{48}$/', $actionToken) !== 1) {
        http_response_code(404);
        exit;
    }
    $record = $registry->findByToken($actionToken);
    if (!is_array($record)) {
        http_response_code(404);
        exit;
    }

    if (isset($_GET['qr'])) {
        try {
            $baseUrl = rtrim((string) $config['public_base_url'], '/');
            header('Content-Type: image/svg+xml; charset=UTF-8');
            header('Cache-Control: no-store, private');
            echo CredentialQr::svg($baseUrl . '/?v=' . rawurlencode($actionToken));
            exit;
        } catch (Throwable) {
            http_response_code(500);
            exit;
        }
    }

    $pdfPath = CredentialApp::pdfPath($record);
    if ($pdfPath === null) {
        http_response_code(404);
        exit('Không tìm thấy bản PDF.');
    }
    $filenamePart = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $record['diploma_number']) ?: 'van-bang';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="ban-xem-thu-' . $filenamePart . '.pdf"');
    header('Content-Length: ' . (string) filesize($pdfPath));
    readfile($pdfPath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string) $_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Phiên đã hết hạn. Tải lại trang rồi thử lại.');
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'login') {
        $lockedUntil = (int) ($_SESSION['credential_admin_locked_until'] ?? 0);
        $candidate = substr((string) ($_POST['password'] ?? ''), 0, 512);
        if ($lockedUntil <= time() && $adminPasswordReady && hash_equals($adminPassword, $candidate)) {
            session_regenerate_id(true);
            $_SESSION['credential_admin'] = true;
            $_SESSION['credential_admin_last_activity'] = time();
            unset($_SESSION['credential_admin_attempts'], $_SESSION['credential_admin_locked_until']);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: admin.php', true, 303);
            exit;
        }
        if ($lockedUntil > time()) {
            $message = 'Đăng nhập tạm khóa do nhập sai nhiều lần. Vui lòng thử lại sau 10 phút.';
        } else {
            $_SESSION['credential_admin_attempts'] = (int) ($_SESSION['credential_admin_attempts'] ?? 0) + 1;
            if ($_SESSION['credential_admin_attempts'] >= 5) {
                $_SESSION['credential_admin_locked_until'] = time() + 600;
                $_SESSION['credential_admin_attempts'] = 0;
                $message = 'Đăng nhập tạm khóa do nhập sai nhiều lần. Vui lòng thử lại sau 10 phút.';
            } else {
                $message = 'Mật khẩu chưa đúng hoặc trang quản trị chưa được cấu hình.';
            }
        }
    } elseif ($action === 'logout' && $loggedIn) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: admin.php', true, 303);
        exit;
    } elseif ($action === 'preview' && $loggedIn && $registry instanceof CredentialRegistry) {
        unset($_SESSION['credential_preview']);
        try {
            $inspection = (new SpreadsheetImporter())->inspectUpload($_FILES['excel_file'] ?? []);
            $mapping = StudentMapper::suggest($inspection['headers']);
            $mapped = StudentMapper::mapRows($inspection['student_rows'], $inspection['headers'], $mapping);
            $programFallback = trim((string) ($_POST['program'] ?? ''));
            foreach ($mapped['students'] as &$student) {
                if (trim((string) ($student['major'] ?? '')) === '' && $programFallback !== '') {
                    $student['major'] = $programFallback;
                }
            }
            unset($student);
            if ($mapped['errors'] !== []) {
                throw new RuntimeException(implode("\n", array_slice($mapped['errors'], 0, 12)));
            }
            if (count($mapped['students']) === 0 || count($mapped['students']) > 30) {
                throw new RuntimeException('Mỗi lần chỉ xử lý từ 1 đến 30 người học để phù hợp giới hạn hosting.');
            }

            $credentialTypes = [
                'so_cap' => 'Chứng chỉ sơ cấp nghề',
                'trung_cap' => 'Bằng tốt nghiệp trung cấp',
                'cao_dang' => 'Bằng tốt nghiệp cao đẳng',
            ];
            $level = (string) ($_POST['level'] ?? 'trung_cap');
            if (!isset($credentialTypes[$level])) {
                throw new RuntimeException('Vui lòng chọn trình độ văn bằng hợp lệ.');
            }
            $credentialType = $credentialTypes[$level];
            $isValidDate = static function (string $value): bool {
                $date = DateTimeImmutable::createFromFormat('!d/m/Y', $value);
                $dateErrors = DateTimeImmutable::getLastErrors();
                return $date !== false
                    && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
                    && $date->format('d/m/Y') === $value;
            };

            $seenDiplomaNumbers = [];
            $preparedStudents = [];
            foreach ($mapped['students'] as $index => $student) {
                foreach ([
                    'diploma_number' => 'số hiệu văn bằng',
                    'diploma_issue_date' => 'ngày cấp văn bằng',
                    'major' => 'nghề/ngành đào tạo',
                ] as $key => $label) {
                    if (trim((string) ($student[$key] ?? '')) === '') {
                        $errors[] = 'Dòng ' . ($index + 2) . ': thiếu ' . $label . '.';
                    }
                }
                foreach (['date_of_birth' => 'ngày sinh', 'diploma_issue_date' => 'ngày cấp văn bằng'] as $key => $label) {
                    $dateValue = (string) ($student[$key] ?? '');
                    if ($dateValue !== '' && !$isValidDate($dateValue)) {
                        $errors[] = 'Dòng ' . ($index + 2) . ': ' . $label . ' không phải ngày hợp lệ dạng ngày/tháng/năm.';
                    }
                }

                $diplomaNumber = trim((string) ($student['diploma_number'] ?? ''));
                if ($diplomaNumber !== '') {
                    $normalizedNumber = mb_strtolower($diplomaNumber, 'UTF-8');
                    if (isset($seenDiplomaNumbers[$normalizedNumber])) {
                        $errors[] = 'Dòng ' . ($index + 2) . ': số hiệu văn bằng bị trùng trong file.';
                    } elseif ($registry->hasDiplomaNumber($credentialType, $diplomaNumber)) {
                        $errors[] = 'Dòng ' . ($index + 2) . ': số hiệu văn bằng đã có trong hệ thống.';
                    }
                    $seenDiplomaNumbers[$normalizedNumber] = true;
                }

                $preparedStudents[] = array_intersect_key($student, array_flip([
                    'student_code', 'full_name', 'date_of_birth', 'place_of_birth', 'major',
                    'graduation_classification', 'diploma_number', 'diploma_issue_date', 'diploma_register_number',
                ]));
            }
            if ($errors !== []) {
                throw new RuntimeException(implode("\n", array_slice($errors, 0, 12)));
            }

            $_SESSION['credential_preview'] = [
                'students' => $preparedStudents,
                'credential_type' => $credentialType,
                'level' => $level,
                'expires_at' => time() + 900,
            ];
            header('Location: admin.php?preview=1', true, 303);
            exit;
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
        }
    } elseif ($action === 'generate' && $loggedIn && $registry instanceof CredentialRegistry && $pdo instanceof PDO) {
        $generatedPaths = [];
        try {
            $pending = $_SESSION['credential_preview'] ?? null;
            if (!is_array($pending) || (int) ($pending['expires_at'] ?? 0) < time()) {
                unset($_SESSION['credential_preview']);
                throw new RuntimeException('Bản xem trước đã hết hạn. Vui lòng tải Excel và đọc lại.');
            }
            $students = $pending['students'];
            $credentialType = (string) $pending['credential_type'];
            if (!is_array($students) || $students === [] || count($students) > 30) {
                unset($_SESSION['credential_preview']);
                throw new RuntimeException('Không tìm thấy dữ liệu xem trước hợp lệ. Vui lòng tải Excel và đọc lại.');
            }
            unset($_SESSION['credential_preview']);

            $credentialDirectory = $root . '/storage/credentials';
            $tempDirectory = $root . '/storage/tmp/chungchi';
            if (!is_dir($credentialDirectory) && !mkdir($credentialDirectory, 0775, true) && !is_dir($credentialDirectory)) {
                throw new RuntimeException('Không thể tạo thư mục riêng để lưu PDF.');
            }
            if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0775, true) && !is_dir($tempDirectory)) {
                throw new RuntimeException('Không thể tạo thư mục tạm của mPDF.');
            }

            $generator = new CredentialPdfGenerator();
            $logoPath = $root . '/public/assets/logo-truong-trung-cap-mien-tay.png';
            $baseUrl = rtrim((string) $config['public_base_url'], '/');
            $pdo->beginTransaction();
            foreach ($students as $student) {
                $issuedToken = CredentialRegistry::newToken();
                $relativePath = 'credentials/' . CredentialRegistry::tokenHash($issuedToken) . '.pdf';
                $pdfPath = $root . '/storage/' . $relativePath;
                $verifyUrl = $baseUrl . '/?v=' . rawurlencode($issuedToken);
                $generatedPaths[] = $pdfPath;
                $generator->render($pdfPath, $tempDirectory, $logoPath, $student, $credentialType, $verifyUrl);
                $registry->createDraft($student, $credentialType, $issuedToken, $relativePath);
                $created[] = [
                    'full_name' => $student['full_name'],
                    'diploma_number' => $student['diploma_number'],
                    'token' => $issuedToken,
                ];
            }
            $pdo->commit();
            $_SESSION['created_credentials'] = $created;
            header('Location: admin.php?created=1', true, 303);
            exit;
        } catch (Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($generatedPaths as $generatedPath) {
                if (is_file($generatedPath)) {
                    unlink($generatedPath);
                }
            }
            $message = $exception->getMessage();
        }
    }
}

if (isset($_GET['created']) && $loggedIn) {
    $created = is_array($_SESSION['created_credentials'] ?? null) ? $_SESSION['created_credentials'] : [];
    unset($_SESSION['created_credentials']);
}

if (isset($_GET['preview']) && $loggedIn) {
    $pending = $_SESSION['credential_preview'] ?? null;
    if (is_array($pending) && (int) ($pending['expires_at'] ?? 0) >= time()) {
        $previewData = $pending;
    } else {
        unset($_SESSION['credential_preview']);
        $message ??= 'Bản xem trước đã hết hạn. Vui lòng tải Excel và đọc lại.';
    }
}

$recent = [];
if ($loggedIn && $registry instanceof CredentialRegistry) {
    try {
        $recent = $registry->recent(30);
    } catch (Throwable) {
        $message = 'Chưa đọc được danh sách văn bằng. Kiểm tra lại bảng credentials trong cơ sở dữ liệu.';
    }
}
$formProgram = (string) ($_POST['program'] ?? '');
if ($formProgram === '' && is_array($previewData) && isset($previewData['students'][0]['major'])) {
    $formProgram = (string) $previewData['students'][0]['major'];
}
$formLevel = is_array($previewData)
    ? (string) $previewData['level']
    : (string) ($_POST['level'] ?? 'trung_cap');

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Quản trị văn bằng · Trường Trung cấp Miền Tây</title>
    <link rel="stylesheet" href="assets/site.css?v=<?= (string) filemtime(__DIR__ . '/assets/site.css') ?>">
</head>
<body>
<header class="site-header">
    <a class="brand" href="./">
        <img src="assets/logo-truong-trung-cap-mien-tay.png" alt="" width="48" height="48">
        <span><strong>TRƯỜNG TRUNG CẤP MIỀN TÂY</strong><small>Quản trị văn bằng</small></span>
    </a>
    <a class="header-caption" href="./">Mở trang tra cứu công khai ↗</a>
</header>
<main class="page-shell admin-shell">
    <section class="intro admin-intro">
        <p class="eyebrow">KHU VỰC CÁN BỘ</p>
        <h1>Văn bằng điện tử</h1>
        <p>Nhập file Excel để tạo bản PDF xem thử và mã QR tra cứu cho từng người học.</p>
    </section>

    <?php if ($configError !== null): ?>
        <div class="admin-notice notice-error" role="alert"><?= nl2br($escape($configError)) ?><br><span>Cần tạo database, nhập bảng credentials, rồi sao chép storage/chungchi-config.example.php thành storage/chungchi-config.php và điền kết nối cùng mật khẩu quản trị tối thiểu 16 ký tự.</span></div>
    <?php endif; ?>
    <?php if ($message !== null): ?>
        <div class="admin-notice <?= $errors !== [] ? 'notice-error' : '' ?>" role="alert"><?= nl2br($escape($message)) ?></div>
    <?php endif; ?>

    <?php if (!$loggedIn): ?>
        <section class="admin-card login-card" aria-labelledby="login-title">
            <h2 id="login-title">Đăng nhập quản trị</h2>
            <p>Chỉ cán bộ được cấp mật khẩu mới có thể tạo bản xem thử.</p>
            <form method="post" class="admin-form">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>">
                <label for="admin-password">Mật khẩu quản trị</label>
                <input id="admin-password" type="password" name="password" autocomplete="current-password" required>
                <button class="download-button" type="submit">Đăng nhập</button>
            </form>
        </section>
    <?php else: ?>
        <div class="admin-toolbar"><span>Đã đăng nhập quản trị</span>
            <form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>"><button class="link-button" type="submit">Đăng xuất</button></form>
        </div>
        <div class="admin-notice notice-warning" role="note"><strong>Chế độ xem thử.</strong> Các PDF hiện được đóng dấu “Chưa có giá trị cấp phát”. Cần mẫu văn bằng đã được trường duyệt trước khi phát hành chính thức.</div>
        <section class="admin-card" aria-labelledby="generate-title">
            <div class="admin-card-heading">
                <div><h2 id="generate-title">Tạo bản xem thử từ Excel</h2><p>File không được lưu lại. Mỗi lần tối đa 30 người học.</p></div>
                <a class="template-download" href="template.php">Tải Excel mẫu</a>
            </div>
            <form method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="action" value="preview">
                <input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>">
                <label for="credential-level">Loại văn bằng</label>
                <select id="credential-level" name="level">
                    <option value="trung_cap" <?= $formLevel === 'trung_cap' ? 'selected' : '' ?>>Bằng tốt nghiệp trung cấp</option>
                    <option value="cao_dang" <?= $formLevel === 'cao_dang' ? 'selected' : '' ?>>Bằng tốt nghiệp cao đẳng</option>
                    <option value="so_cap" <?= $formLevel === 'so_cap' ? 'selected' : '' ?>>Chứng chỉ sơ cấp nghề</option>
                </select>
                <label for="credential-program">Nghề/ngành đào tạo</label>
                <input id="credential-program" type="text" name="program" maxlength="255" value="<?= $escape($formProgram) ?>" placeholder="Ví dụ: Điện công nghiệp">
                <p class="form-hint">Nếu Excel có cột nghề/ngành, hệ thống dùng dữ liệu theo từng dòng; ô này điền các dòng còn trống.</p>
                <label for="excel-file">File dữ liệu</label>
                <input id="excel-file" type="file" name="excel_file" accept=".xlsx" required>
                <p class="form-hint">Cần có họ tên, mã người học, ngày sinh, nghề/ngành, số hiệu văn bằng và ngày cấp. Chỉ nhận XLSX tối đa 10 MB.</p>
                <button class="download-button" type="submit">Đọc và xem trước</button>
            </form>
        </section>

        <?php if (is_array($previewData)): ?>
            <section class="admin-card preview-card" aria-labelledby="preview-title">
                <div class="admin-card-heading">
                    <div>
                        <p class="eyebrow">BƯỚC 2 · ĐỐI CHIẾU</p>
                        <h2 id="preview-title">Xem lại <?= count($previewData['students']) ?> hồ sơ</h2>
                        <p><?= $escape($previewData['credential_type']) ?> · Kiểm tra tên, ngày sinh và số hiệu trước khi tạo PDF.</p>
                    </div>
                    <span class="draft-badge">BẢN XEM THỬ</span>
                </div>
                <div class="recent-table-wrap">
                    <table class="recent-table">
                        <thead><tr><th>TT / Mã</th><th>Người học</th><th>Ngày sinh</th><th>Nghề/ngành</th><th>Số hiệu</th><th>Ngày cấp</th></tr></thead>
                        <tbody>
                        <?php foreach ($previewData['students'] as $student): ?>
                            <tr>
                                <td><?= $escape($student['student_code']) ?></td>
                                <td><?= $escape($student['full_name']) ?></td>
                                <td><?= $escape($student['date_of_birth']) ?></td>
                                <td><?= $escape($student['major'] !== '' ? $student['major'] : '—') ?></td>
                                <td><?= $escape($student['diploma_number']) ?></td>
                                <td><?= $escape($student['diploma_issue_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="preview-actions">
                    <form method="post">
                        <input type="hidden" name="action" value="generate">
                        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>">
                        <button class="download-button" type="submit">Đúng rồi, tạo PDF và QR</button>
                    </form>
                    <a class="link-button" href="admin.php">Hủy bản xem trước</a>
                    <span class="form-hint">Bản xem trước hết hạn sau 15 phút. Dữ liệu Excel gốc không được lưu.</span>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($created !== []): ?>
            <section class="admin-card created-card" aria-labelledby="created-title">
                <h2 id="created-title">Đã tạo <?= count($created) ?> bản xem thử</h2>
                <p>Lưu các PDF này về máy. QR đã được in trực tiếp trên từng trang.</p>
                <div class="created-list">
                    <?php foreach ($created as $item): ?>
                        <?php $publicUrl = rtrim((string) $config['public_base_url'], '/') . '/?v=' . rawurlencode($item['token']); ?>
                        <article class="created-item">
                            <img src="?qr=<?= $escape($item['token']) ?>" alt="Mã QR của <?= $escape($item['full_name']) ?>" width="92" height="92">
                            <div><strong><?= $escape($item['full_name']) ?></strong><span>Số hiệu: <?= $escape($item['diploma_number']) ?></span><a href="?pdf=<?= $escape($item['token']) ?>">Tải bản PDF xem thử</a><a href="<?= $escape($publicUrl) ?>" target="_blank" rel="noopener">Mở trang tra cứu</a></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="admin-card recent-card" aria-labelledby="recent-title">
            <h2 id="recent-title">Các bản gần đây</h2>
            <?php if ($recent === []): ?><p class="form-hint">Chưa có văn bằng nào trong hệ thống.</p><?php else: ?>
                <div class="recent-table-wrap"><table class="recent-table"><thead><tr><th>Người học</th><th>Loại</th><th>Số hiệu</th><th>Ngày cấp</th><th>Trạng thái</th></tr></thead><tbody>
                    <?php foreach ($recent as $item): ?><tr><td><?= $escape($item['full_name']) ?></td><td><?= $escape($item['credential_type']) ?></td><td><?= $escape($item['diploma_number']) ?></td><td><?= $escape($item['diploma_issue_date']) ?></td><td><?= $escape($item['status'] === 'draft' ? 'Bản xem thử' : ($item['status'] === 'issued' ? 'Đã cấp' : 'Đã thu hồi')) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<footer class="site-footer"><span>Trường Trung cấp Miền Tây</span><span>Không chia sẻ bản PDF hoặc mã QR ngoài mục đích xác thực.</span></footer>
</body>
</html>
