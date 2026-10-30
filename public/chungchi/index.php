<?php

declare(strict_types=1);

use AIVANBAN\Services\CredentialApp;
use AIVANBAN\Services\CredentialRegistry;
use AIVANBAN\Services\CredentialQr;
use AIVANBAN\Support\Html;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'; object-src 'self'");
header('X-Robots-Tag: noindex, nofollow, noarchive');

$escape = [Html::class, 'escape'];
$logoUrl = 'assets/logo-truong-trung-cap-mien-tay.png';
$token = strtolower(trim((string) ($_GET['v'] ?? $_GET['pdf'] ?? $_GET['qr'] ?? '')));
$tokenIsValid = preg_match('/^[a-f0-9]{48}$/', $token) === 1;
$config = null;
$record = null;
$registry = null;
$serviceReady = false;

try {
    $config = CredentialApp::config();
    $registry = new CredentialRegistry(CredentialApp::connect($config));
    $serviceReady = true;
    if ($tokenIsValid) {
        $record = $registry->findByToken($token);
    }
} catch (Throwable) {
    $serviceReady = false;
}

if (isset($_GET['qr']) && $tokenIsValid && is_array($record) && $record['status'] !== 'revoked') {
    try {
        $baseUrl = rtrim((string) $config['public_base_url'], '/');
        $svg = CredentialQr::svg($baseUrl . '/?v=' . rawurlencode($token));
        header('Content-Type: image/svg+xml; charset=UTF-8');
        header('Cache-Control: private, no-store');
        echo $svg;
        exit;
    } catch (Throwable) {
        http_response_code(500);
        exit;
    }
}

if (isset($_GET['pdf']) && $tokenIsValid && is_array($record) && $record['status'] !== 'revoked') {
    $pdfPath = CredentialApp::pdfPath($record);
    if ($pdfPath === null) {
        http_response_code(404);
        exit('Không tìm thấy tệp văn bằng.');
    }

    $filenamePart = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $record['diploma_number']) ?: 'van-bang';
    $download = (string) ($_GET['download'] ?? '') === '1';
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="van-bang-' . $filenamePart . '.pdf"');
    header('Content-Length: ' . (string) filesize($pdfPath));
    header('Cache-Control: private, no-store, max-age=0');
    readfile($pdfPath);
    exit;
}

$view = 'home';
if (isset($_GET['v'])) {
    if (!$tokenIsValid || !$serviceReady || !is_array($record)) {
        $view = 'not-found';
    } elseif ($record['status'] === 'revoked') {
        $view = 'revoked';
    } else {
        $view = 'credential';
    }
}

$statusLabel = match ($record['status'] ?? '') {
    'issued' => 'Đã cấp',
    'draft' => 'Bản xem thử',
    'revoked' => 'Đã thu hồi',
    default => 'Chưa xác định',
};
$pdfUrl = '?pdf=' . rawurlencode($token);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#f3f7f3">
    <title><?= $view === 'credential' ? 'Văn bằng ' . $escape($record['full_name']) : 'Tra cứu văn bằng' ?> · Trường Trung cấp Miền Tây</title>
    <link rel="stylesheet" href="assets/site.css?v=<?= (string) filemtime(__DIR__ . '/assets/site.css') ?>">
</head>
<body>
<header class="site-header">
    <a class="brand" href="./">
        <img src="<?= $escape($logoUrl) ?>" alt="" width="48" height="48">
        <span><strong>TRƯỜNG TRUNG CẤP MIỀN TÂY</strong><small>Tra cứu văn bằng</small></span>
    </a>
    <span class="header-caption">Cổng thông tin văn bằng</span>
</header>

<main class="page-shell">
    <?php if ($view === 'home'): ?>
        <section class="intro">
            <p class="eyebrow">CỔNG XÁC THỰC</p>
            <h1>Tra cứu văn bằng</h1>
            <p>Quét mã QR trên văn bằng để xem bản PDF đầy đủ và đối chiếu trạng thái tra cứu.</p>
        </section>
        <section class="empty-state" aria-labelledby="scan-title">
            <div class="scan-symbol" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <h2 id="scan-title">Mở bằng mã QR</h2>
            <p>Dùng camera điện thoại quét mã QR in trên văn bằng. Đường dẫn sẽ đưa bạn đến đúng hồ sơ cần tra cứu.</p>
            <?php if (!$serviceReady): ?>
                <p class="setup-note">Trang tra cứu đang chờ cấu hình máy chủ.</p>
            <?php endif; ?>
        </section>
    <?php elseif ($view === 'credential'): ?>
        <section class="intro result-intro">
            <p class="eyebrow">KẾT QUẢ TRA CỨU</p>
            <h1>Thông tin văn bằng</h1>
            <p>Đối chiếu thông tin dưới đây với bản PDF nguyên văn do trường phát hành.</p>
        </section>

        <?php if ($record['status'] === 'draft'): ?>
            <div class="status-banner status-draft" role="status">
                <strong>BẢN XEM THỬ — CHƯA CÓ GIÁ TRỊ CẤP PHÁT</strong>
                <span>Đây là bản dựng thử. Chưa xác nhận mẫu chính thức, chữ ký hoặc tình trạng cấp bằng.</span>
            </div>
        <?php else: ?>
            <div class="status-banner status-issued" role="status">
                <strong>Văn bằng đang có hiệu lực trong hệ thống</strong>
                <span>Trạng thái: <?= $escape($statusLabel) ?></span>
            </div>
        <?php endif; ?>

        <section class="credential-summary" aria-label="Thông tin xác thực">
            <div class="summary-heading">
                <div>
                    <span class="summary-label">Người được cấp</span>
                    <h2><?= $escape($record['full_name']) ?></h2>
                </div>
                <span class="status-pill <?= $record['status'] === 'issued' ? 'pill-issued' : 'pill-draft' ?>"><?= $escape($statusLabel) ?></span>
            </div>
            <dl class="summary-grid">
                <div><dt>Loại văn bằng</dt><dd><?= $escape($record['credential_type']) ?></dd></div>
                <div><dt>Số hiệu</dt><dd><?= $escape($record['diploma_number']) ?></dd></div>
                <div><dt>Nghề/ngành đào tạo</dt><dd><?= $escape($record['program'] !== '' ? $record['program'] : '—') ?></dd></div>
                <div><dt>Ngày cấp</dt><dd><?= $escape($record['diploma_issue_date']) ?></dd></div>
                <?php if ($record['status'] === 'issued' && $record['graduation_classification'] !== ''): ?>
                    <div><dt>Xếp loại</dt><dd><?= $escape($record['graduation_classification']) ?></dd></div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="document-section" aria-labelledby="document-title">
            <div class="document-heading">
                <div><h2 id="document-title">Bản PDF đầy đủ</h2><p>Văn bằng được gắn với mã tra cứu này.</p></div>
                <a class="download-button" href="<?= $escape($pdfUrl . '&download=1') ?>">Tải PDF</a>
            </div>
            <div class="pdf-frame-wrap">
                <iframe class="pdf-frame" src="<?= $escape($pdfUrl) ?>#toolbar=0" title="Bản văn bằng PDF"></iframe>
                <div class="pdf-fallback">Trình duyệt không hiển thị PDF trực tiếp. <a href="<?= $escape($pdfUrl) ?>">Mở văn bằng</a>.</div>
            </div>
        </section>
    <?php elseif ($view === 'revoked'): ?>
        <section class="message-state state-revoked" role="alert">
            <p class="eyebrow">KẾT QUẢ TRA CỨU</p>
            <h1>Văn bằng đã bị thu hồi</h1>
            <p>Vui lòng liên hệ Trường Trung cấp Miền Tây để được xác minh thêm.</p>
        </section>
    <?php else: ?>
        <section class="message-state" role="status">
            <p class="eyebrow">KẾT QUẢ TRA CỨU</p>
            <h1>Không tìm thấy văn bằng</h1>
            <p>Mã QR không hợp lệ hoặc hồ sơ chưa được đăng ký trên hệ thống. Hãy kiểm tra lại mã hoặc liên hệ nhà trường.</p>
            <a class="text-link" href="./">← Về trang tra cứu</a>
        </section>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <span>Trường Trung cấp Miền Tây</span>
    <span>Thông tin trên trang dùng để kiểm tra văn bằng điện tử.</span>
</footer>
</body>
</html>
