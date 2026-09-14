<?php
declare(strict_types=1);

namespace AIVANBAN\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/** Layouts transcribed from the user's photographed pages 3–70; output is A4. */
trait PhotoFormTemplates
{
    use PhotoTeachingForms;
    use PhotoPlanForms;
    use PhotoRegisterForms;

    private function pc(string $text, int $cols = 1, int $rows = 1, bool $raw = false): array
    {
        return ['text' => $text, 'cols' => $cols, 'rows' => $rows, 'raw' => $raw];
    }

    /** Each row must occupy exactly the declared leaf columns, including rowspans. */
    private function pGrid(array $widths, array $heads, array $body, int $minimum = 0, float $height = 6, float $font = 9, float $headerHeight = 0, float $gap = 2): string
    {
        $n = count($widths);
        while (count($body) < $minimum) $body[] = array_fill(0, $n, '');
        $total = array_sum($widths);
        $html = '<table class="photo-grid" style="font-size:' . $font . 'pt;margin:'.$gap.'mm 0" autosize="1">';
        foreach (['thead' => $heads, 'tbody' => $body] as $section => $rows) {
            if ($rows === []) continue;
            $html .= '<' . $section . '>';
            $occupied = [];
            foreach ($rows as $ri => $row) {
                $html .= '<tr>'; $ci = 0;
                foreach ($row as $cell) {
                    while (($occupied[$ci] ?? 0) > $ri) $ci++;
                    $cell = is_array($cell) ? $cell : $this->pc((string) $cell);
                    $span = $cell['cols']; $down = $cell['rows'];
                    if ($ci + $span > $n) throw new \LogicException('Bảng có ô vượt số cột khai báo.');
                    for ($j = $ci; $j < $ci + $span; $j++) {
                        if (($occupied[$j] ?? 0) > $ri) throw new \LogicException('Ô gộp chồng lên ô khác.');
                        $occupied[$j] = $ri + $down;
                    }
                    $w = array_sum(array_slice($widths, $ci, $span)) / $total * 100;
                    $tag = $section === 'thead' ? 'th' : 'td';
                    $html .= '<' . $tag . ' width="' . round($w, 5) . '%" colspan="' . $span . '" rowspan="' . $down . '" style="' . ($tag === 'td' ? 'height:' . $height . 'mm;' : ($headerHeight > 0 ? 'height:'.$headerHeight.'mm;' : '')) . '">'
                        . ($cell['raw'] ? $cell['text'] : nl2br($this->e($cell['text']))) . '</' . $tag . '>';
                    $ci += $span;
                }
                while (($occupied[$ci] ?? 0) > $ri) $ci++;
                if ($ci !== $n) throw new \LogicException('Bảng thiếu cột ở hàng ' . ($ri + 1) . ': ' . $ci . '/' . $n);
                $html .= '</tr>';
            }
            $html .= '</' . $section . '>';
        }
        return $html . '</table>';
    }

    private function pLabel(int $number): string
    {
        // The exported document is the finished data-filled form.  The form
        // number belongs to the internal catalogue, not to the printed page.
        return '';
    }

    private function pOfficialHeader(array $course, bool $compact = false): string
    {
        return '<table class="official-head' . ($compact ? ' official-head-compact' : '') . '"><tr>'
            . '<td class="official-school"><b>' . $this->e($this->school($course)) . '</b></td>'
            . '<td class="official-state"><b>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</b><br>'
            . '<span>Độc lập - Tự do - Hạnh phúc</span><div class="official-rule">&nbsp;</div></td>'
            . '</tr></table>';
    }

    private function pPage(int $number, string $title = '', string $orientation = 'P'): string
    {
        return '<pagebreak orientation="' . $orientation . '" />' . $this->pLabel($number)
            . ($title === '' ? '' : '<h2>' . $this->e($title) . '</h2>');
    }

    private function pValue(string $value, string $blank = '........................'): string
    {
        return $this->e($value === '' ? $blank : $value);
    }

    private function pLines(int $count, string $text = '', float $height = 6): string
    {
        $html = $text === '' ? '' : '<div class="written">' . nl2br($this->e($text)) . '</div>';
        $html .= '<table width="100%" style="border-collapse:collapse;border:0;margin:0"><tbody>';
        for ($i = 0; $i < $count; $i++) $html .= '<tr><td style="height:' . $height . 'mm;border:0;border-bottom:.35pt dotted #000;padding:0;font-size:1pt">&nbsp;</td></tr>';
        return $html . '</tbody></table>';
    }

    private function pSign(string $left, string $right = '', string $leftNote = '(Ký tên, đóng dấu)'): string
    {
        return '<table class="photo-sign"><tr><td>' . ($left === '' ? '' : '<b>' . $this->e($left) . '</b><br><i>' . $this->e($leftNote) . '</i>') . '</td><td>'
            . ($right === '' ? '' : '<i>Ngày .... tháng .... năm ....</i><br><b>' . $this->e($right) . '</b>' . (str_contains($right, 'HIỆU TRƯỞNG') ? '<br><i>(Ký tên, đóng dấu)</i>' : '')) . '</td></tr></table>';
    }

    private function pCover(int $number, string $title, array $course, array $fields = []): string
    {
        $supervisory = $this->v($course, 'coquanchuquan')
            ?: $this->v($course, 'donviquanlytructiep')
            ?: self::DEFAULT_SUPERVISORY_BODY;
        $detail = '';
        foreach ($fields as $label => $value) {
            $detail .= '<p><b>' . $this->e($label) . ':</b> ' . $this->pValue((string) $value) . '</p>';
        }

        $titleBlock = '<div class="cover-title">' . $this->e($title) . '</div>';
        if ($fields === []) {
            $titleBlock .= '<div class="cover-number">Quyển số: ' . $this->pValue($this->v($course, 'quyenso')) . '</div>';
        } else {
            $titleBlock .= '<div class="cover-fields">' . $detail . '</div>'
                . '<div class="cover-year"><b>Năm học:</b> ' . $this->pValue($this->v($course, 'namhoc')) . '</div>';
        }

        return $this->pLabel($number)
            . '<div class="photo-cover-frame"><div class="photo-cover-border-inner"><table class="photo-cover-inner">'
            . '<tr><td class="cover-identity"><div class="cover-agency">' . $this->e($supervisory) . '</div>'
            . '<div class="cover-school">' . $this->e($this->school($course)) . '</div></td></tr>'
            . '<tr><td class="cover-main">' . $titleBlock . '</td></tr>'
            . '<tr><td class="cover-bottom">&nbsp;</td></tr>'
            . '</table></div></div>';
    }

    private function pCertificate(int $number, string $title, array $course): string
    {
        $sign = in_array($number, [9, 10, 11], true) ? 'HIỆU TRƯỞNG/GIÁM ĐỐC' : 'HIỆU TRƯỞNG';
        $bookDescription = match ($number) {
            9 => 'Sổ cấp chứng chỉ sơ cấp nghề này',
            10 => 'Sổ cấp bản sao chứng chỉ sơ cấp nghề này',
            11, 15 => 'Sổ quản lý học sinh này',
            13 => 'Sổ cấp bằng TNTCN này',
            14 => 'Sổ cấp bằng TNCĐN này',
            16 => 'Sổ quản lý sinh viên này',
            17 => 'Sổ cấp bản sao bằng TNTCN này',
            18 => 'Sổ cấp bản sao bằng TNCĐN này',
            default => 'Sổ này',
        };
        $left = '<h3>CHỨNG NHẬN</h3><p>'.$this->e($bookDescription).' có: ............ trang</p><p>Đánh số trang từ số: ............</p><p>Đến số: ............</p><p>Mở sổ ngày .... tháng .... năm ....</p><b>'.$sign.'</b><br><i>(Ký tên, đóng dấu)</i>';
        $right = '<h3>CHỨNG NHẬN</h3><p>Số thứ tự đăng ký từ số: ............</p><p>Đến số: ............</p><p>Khóa sổ ngày .... tháng .... năm ....</p><br><b>'.$sign.'</b><br><i>(Ký tên, đóng dấu)</i>';
        return '<pagebreak orientation="P" />'.$this->pLabel($number)
            . '<div class="photo-certificate-frame"><table class="photo-certificate-inner">'
            . '<tr><td class="certificate-title">'.$this->e($title).'<div class="certificate-number">Quyển số: '.$this->pValue($this->v($course,'quyenso')).'</div></td></tr>'
            . '<tr><td class="certificate-block"><table class="photo-certificate"><tr><td>'.$left.'</td><td>'.$right.'</td></tr></table></td></tr>'
            . '<tr><td class="certificate-bottom">&nbsp;</td></tr>'
            . '</table></div>';
    }

    private function renderPdf(string $pdfDir, string $tempDir, string $filename, array $format, string $html): string
    {
        $path = $pdfDir . DIRECTORY_SEPARATOR . $filename;
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => $format, 'tempDir' => $tempDir, 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 14, 'margin_footer' => 6, 'default_font' => 'dejavuserif', 'default_font_size' => 10]);
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->keep_table_proportions = true;
        $mpdf->SetTitle($filename);
        $mpdf->SetAuthor('AIVANBAN');
        // Do not stamp draft text or a watermark on the generated forms.
        // The application already keeps the form selection and source data in
        // its own workflow; the document itself should remain clean to print.
        $mpdf->showWatermarkText = false;
        $mpdf->WriteHTML($this->pCss());
        $mpdf->WriteHTML($html);
        $mpdf->Output($path, Destination::FILE);
        return $path;
    }

    private function pCss(): string
    {
        return '<style>
body{font-family:dejavuserif;font-size:10pt;line-height:1.25;color:#000}
h1{text-align:center;font-size:16pt;margin:6mm 0 2mm}h2{text-align:center;font-size:12pt;margin:5mm 0 4mm}h3{font-size:10pt;margin:4mm 0 2mm}p{margin:1mm 0}.center{text-align:center}.photo-label{text-align:right;font-size:7.5pt;line-height:1.3;margin-bottom:4mm}
.official-head{border-collapse:collapse;width:100%;table-layout:fixed;margin:0 0 5mm}.official-head td{width:50%;border:0;padding:0 2mm;text-align:center;vertical-align:top;font-size:9.5pt;line-height:1.3}.official-head-compact{margin-top:4mm;margin-bottom:3mm}.official-head-compact td{font-size:9pt}.official-state b,.official-school b{font-weight:bold}.official-rule{border-top:.7pt solid #000;width:34mm;height:1mm;margin:1.2mm auto 0}
.photo-grid{border-collapse:collapse;width:100%;margin:2mm 0;table-layout:fixed;page-break-inside:avoid}.photo-grid th,.photo-grid td{border:.65pt solid #000;padding:.75mm 1mm;vertical-align:middle;line-height:1.15;word-wrap:break-word}.photo-grid th{text-align:center;font-weight:bold;background:#fff}.photo-grid td{text-align:left;background:#fff}.photo-grid .photo-grid{margin:0}
.photo-cover-frame{border:.7pt solid #000;width:100%;height:245mm;margin-top:3mm;box-sizing:border-box;padding:1.2mm}.photo-cover-border-inner{border:.7pt solid #000;width:100%;height:242mm;box-sizing:border-box;padding:4mm}.photo-cover-inner{border-collapse:collapse;width:100%;height:232mm;table-layout:fixed;text-align:center}.photo-cover-inner td{border:0;padding:0 8mm;vertical-align:middle}.cover-identity{height:50mm;vertical-align:top!important;padding-top:6mm!important}.cover-agency{font-size:11pt;font-weight:bold;text-transform:uppercase;line-height:1.3;margin-bottom:5mm}.cover-school{font-size:13pt;font-weight:bold;text-transform:uppercase;line-height:1.35}.cover-main{height:111mm}.cover-title{font-size:20pt;font-weight:bold;line-height:1.45;margin:0 auto 5mm;max-width:160mm}.cover-number{font-size:13pt;font-weight:bold}.cover-year{font-size:11pt;margin-top:7mm}.cover-fields{text-align:left;margin:8mm auto 0;width:128mm;line-height:1.65;font-size:10.5pt}.cover-fields p{margin:1.5mm 0}.cover-bottom{height:71mm}.photo-certificate-frame{border:.9pt solid #000;width:100%;height:245mm;margin-top:3mm;box-sizing:border-box;padding:5mm}.photo-certificate-inner{border-collapse:collapse;width:100%;height:233mm;table-layout:fixed}.photo-certificate-inner td{border:0}.certificate-title{text-align:center;font-size:19pt;font-weight:bold;line-height:1.42;height:100mm;padding:30mm 8mm 0;vertical-align:middle;box-sizing:border-box}.certificate-number{font-size:13pt;margin-top:4mm}.certificate-block{height:76mm;vertical-align:top}.certificate-bottom{height:57mm}.photo-certificate{border-collapse:collapse;width:100%;table-layout:fixed;margin:0}.photo-certificate td{width:50%;border:0;vertical-align:top;text-align:center;font-size:10pt;padding:2mm 3mm;line-height:1.55}.photo-certificate p{margin:1mm 0}.photo-certificate h3{text-align:center;font-size:11pt;margin:0 0 2mm}
 .photo-sign{width:100%;margin-top:10mm}.photo-sign td{width:50%;height:25mm;text-align:center;vertical-align:top;font-size:10pt}.photo-head{width:100%;margin:1mm 0 4mm}.photo-head td{width:50%;text-align:center;vertical-align:top;font-size:9pt}.written{line-height:1.5}.photo-legend{width:100%;font-size:8.5pt;text-align:center;margin:3mm 0}.photo-legend td{width:25%;padding:2mm}.photo-legend .key{border:.65pt solid #000;width:18mm;height:3mm;margin:auto}.lesson-meta{width:100%;margin:6mm 0}.lesson-meta td{width:50%;vertical-align:top;font-size:10pt;line-height:1.8}.lesson-table .photo-grid td{vertical-align:top}.bio-table{width:100%;border-collapse:collapse;margin-bottom:4mm}.bio-table>tbody>tr>td{vertical-align:top}.bio-table p{font-size:8.3pt;line-height:1.35;margin:0 0 1.1mm}.bio-register{width:34mm;padding:0 4mm 0 0}.bio-content{padding-left:1mm}.registration-frame{width:30mm;border-collapse:collapse;table-layout:fixed;margin:0 auto}.registration-frame td{width:30mm;border:.65pt solid #000;text-align:center;font-size:9pt;padding:0}.registration-number{height:10mm;vertical-align:middle}.registration-photo{height:40mm;vertical-align:middle;line-height:1.25}.profile-pair{width:100%;border-collapse:collapse}.profile-pair>tbody>tr>td{width:50%;padding:0;vertical-align:top}.grad-summary{font-size:8pt;line-height:1.7}.grad-summary p{margin:2mm}.usage{font-size:11pt;line-height:1.65;text-align:justify}.usage p{margin:3mm 0}
</style>';
    }
}
