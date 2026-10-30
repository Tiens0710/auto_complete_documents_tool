<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Html;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

final class CredentialPdfGenerator
{
    public function render(string $path, string $tempDir, string $logoPath, array $student, string $credentialType, string $verifyUrl): void
    {
        foreach ([dirname($path), $tempDir] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Không thể tạo thư mục tạm để dựng văn bằng.');
            }
        }

        $escape = [Html::class, 'escape'];
        $qr = CredentialQr::pngDataUri($verifyUrl);
        $logo = is_file($logoPath) ? '<img class="school-logo" src="' . $escape($logoPath) . '" alt="">' : '';
        $field = static fn (string $label, string $value): string =>
            '<div class="detail"><span>' . Html::escape($label) . '</span><strong>' . Html::escape($value !== '' ? $value : '—') . '</strong></div>';

        $documentTitle = str_starts_with($credentialType, 'Chứng chỉ')
            ? 'CHỨNG CHỈ SƠ CẤP NGHỀ'
            : 'BẰNG TỐT NGHIỆP';

        $html = '<!doctype html><html lang="vi"><head><meta charset="utf-8"><style>
            @page { margin: 10mm; }
            body { font-family: dejavusans, sans-serif; color: #173d2b; }
            .sheet { border: 2px solid #246943; padding: 8mm; position: relative; }
            .inner { border: 1px solid #9daf9f; padding: 7mm 8mm; text-align: center; }
            .header { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
            .header td { width: 50%; vertical-align: middle; }
            .school-logo { width: 20mm; height: 20mm; }
            .national { font-size: 10pt; font-weight: bold; }
            .motto { font-size: 9pt; margin-top: 2mm; }
            .institution { color: #246943; font-size: 11pt; font-weight: bold; }
            .draft { color: #a12727; border: 1px solid #a12727; padding: 1.5mm 3mm; font-size: 8pt; font-weight: bold; display: inline-block; margin: 1mm 0 4mm; }
            h1 { color: #1e5c3a; font-size: 22pt; line-height: 1.2; margin: 1mm 0; }
            .subtitle { color: #365c46; font-size: 12pt; font-weight: bold; margin-bottom: 5mm; }
            .lead { font-size: 11pt; margin: 0 0 2mm; }
            .name { font-size: 18pt; font-weight: bold; color: #183d2b; margin: 1mm 0 5mm; }
            .details { width: 100%; text-align: left; border-collapse: collapse; margin-top: 2mm; }
            .details td { width: 50%; padding: 2mm 3mm; vertical-align: top; }
            .detail span { display:block; color: #52705d; font-size: 9pt; margin-bottom: 1.5mm; }
            .detail strong { display:block; font-size: 11pt; color: #173d2b; }
            .issue { margin-top: 3mm; font-size: 11pt; }
            .bottom { width: 100%; margin-top: 6mm; border-collapse: collapse; }
            .bottom td { width: 50%; vertical-align: bottom; }
            .qr { width: 25mm; height: 25mm; }
            .verify { font-size: 8pt; color: #486653; }
            .signature-space { height: 14mm; }
            .signature-label { font-size: 10pt; font-weight: bold; }
            .url { font-size: 7pt; color: #486653; word-break: break-all; margin-top: 2mm; }
        </style></head><body><div class="sheet"><div class="inner">
            <table class="header"><tr><td>' . $logo . '<div class="institution">TRƯỜNG TRUNG CẤP MIỀN TÂY</div></td><td><div class="national">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div><div class="motto">Độc lập - Tự do - Hạnh phúc</div></td></tr></table>
            <div class="draft">BẢN XEM THỬ — CHƯA CÓ GIÁ TRỊ CẤP PHÁT</div>
            <h1>' . $escape($documentTitle) . '</h1>
            <div class="subtitle">' . $escape($credentialType) . '</div>
            <p class="lead">Hiệu trưởng Trường Trung cấp Miền Tây cấp cho</p>
            <div class="name">' . $escape($student['full_name']) . '</div>
            <table class="details"><tr><td>' . $field('Ngày sinh', $student['date_of_birth']) . '</td><td>' . $field('Nơi sinh', $student['place_of_birth']) . '</td></tr>
                <tr><td>' . $field('Nghề/ngành đào tạo', $student['major']) . '</td><td>' . $field('Xếp loại', $student['graduation_classification']) . '</td></tr>
                <tr><td>' . $field('Số hiệu văn bằng', $student['diploma_number']) . '</td><td>' . $field('Số vào sổ cấp bằng', $student['diploma_register_number']) . '</td></tr></table>
            <div class="issue">Ngày cấp: <strong>' . $escape($student['diploma_issue_date']) . '</strong></div>
            <table class="bottom"><tr><td><img class="qr" src="' . $escape($qr) . '" alt="Mã QR tra cứu bản xem thử"><div class="verify">Quét mã để xem bản PDF và trạng thái hồ sơ</div></td><td><div class="signature-label">HIỆU TRƯỞNG</div><div class="signature-space"></div><div class="signature-label">(Chưa có chữ ký)</div></td></tr></table>
            <div class="url">' . $escape($verifyUrl) . '</div>
        </div></div></body></html>';

        $pdf = new Mpdf([
            'format' => 'A4',
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $pdf->SetTitle('Bản xem thử văn bằng - ' . $student['diploma_number']);
        $pdf->SetAuthor('Trường Trung cấp Miền Tây - bản xem thử');
        $pdf->WriteHTML($html);
        $pdf->Output($path, Destination::FILE);
    }
}
