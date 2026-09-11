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
        // Use table rows instead of absolute positioning. mPDF resolves absolute
        // offsets from the current cursor, which made the cover title collapse at
        // the top and hid the lower frame on A4 pages.
        $school = $this->school($course);
        $agency = $this->v($course, 'coquanchuchuan') ?: $this->v($course, 'coquanchuquan');
        $detail = '';
        if ($fields === []) {
            $detail = '<tr style="height:16mm"><td class="cover-number">Quyển số: ' . $this->pValue($this->v($course, 'quyenso')) . '</td></tr>';
        } else {
            foreach ($fields as $label => $value) $detail .= '<p>' . $this->e($label) . ': ' . $this->pValue((string) $value) . '</p>';
        }
        $inside = ($agency === '' ? '' : '<div style="text-align:center;font-size:11pt;padding-top:10mm">' . $this->e($agency) . '</div>')
            . '<div style="text-align:center;font-size:13pt;font-weight:bold;margin-top:8mm">' . $this->e($school) . '</div>'
            . '<div style="height:42mm">&nbsp;</div>'
            . '<div style="text-align:center;font-size:20pt;font-weight:bold;line-height:1.45;min-height:58mm">' . $this->e($title) . '</div>';
        if ($fields === []) {
            $inside .= '<div style="text-align:center;font-size:12pt;margin-top:4mm">Quyển số: ' . $this->pValue($this->v($course, 'quyenso')) . '</div>';
        } else {
            $inside .= '<div style="font-size:11pt;line-height:1.8;text-align:left;margin:4mm 14mm 0">' . $detail . '</div>'
                . '<div style="text-align:center;font-size:11pt;margin-top:7mm">Năm học: ' . $this->pValue($this->v($course, 'namhoc')) . '</div>';
        }
        return $this->pLabel($number)
            . '<div style="border:1.5pt double #000;width:100%;height:245mm;margin-top:3mm;box-sizing:border-box;padding:0 5mm">'
            . $inside . '</div>';
    }

    private function pCertificate(int $number, string $title, array $course): string
    {
        $sign = in_array($number, [9, 10, 11], true) ? 'HIỆU TRƯỞNG/GIÁM ĐỐC' : 'HIỆU TRƯỞNG';
        // Keep this page in normal table flow.  Absolute children were rendered
        // relative to the label's current cursor by mPDF and pushed the frame
        // down on some forms (especially M9/M10/M13/M14/M17/M18).
        $left = '<h3>CHỨNG NHẬN</h3><p>Sổ này có: ............ trang</p><p>Đánh số trang từ số: ............</p><p>Đến số: ............</p><p>Mở sổ ngày .... tháng .... năm ....</p><br><b>'.$sign.'</b><br><i>(Ký tên, đóng dấu)</i>';
        $right = '<h3>CHỨNG NHẬN</h3><p>Số thứ tự đăng ký từ số: ............</p><p>Đến số: ............</p><p>Khóa sổ ngày .... tháng .... năm ....</p><br><b>'.$sign.'</b><br><i>(Ký tên, đóng dấu)</i>';
        return '<pagebreak orientation="P" />'.$this->pLabel($number)
            . '<div style="border:1.5pt double #000;width:100%;height:245mm;margin-top:3mm;box-sizing:border-box;padding:0 5mm">'
            . '<div style="text-align:center;font-size:18pt;font-weight:bold;line-height:1.4;padding-top:50mm;height:105mm;box-sizing:border-box">'.$this->e($title).'<br><br><span style="font-size:12pt">Quyển số: '.$this->pValue($this->v($course,'quyenso')).'</span></div>'
            . '<table class="photo-certificate"><tr><td>'.$left.'</td><td>'.$right.'</td></tr></table>'
            . '</div>';
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
.photo-grid{border-collapse:collapse;width:100%;margin:2mm 0;table-layout:fixed}.photo-grid th,.photo-grid td{border:.5pt solid #000;padding:.6mm .8mm;vertical-align:middle;word-wrap:break-word}.photo-grid th{text-align:center;font-weight:bold;background:#fff}.photo-grid td{text-align:left;background:#fff}.photo-grid .photo-grid{margin:0}
.photo-cover-frame{border-collapse:collapse;border:1.5pt double #000;width:100%;height:245mm;margin-top:3mm}.photo-cover-frame>tbody>tr>td{padding:5mm;vertical-align:top}.photo-cover-inner{border-collapse:collapse;width:100%;height:235mm;text-align:center}.photo-cover-inner td{border:0;vertical-align:middle}.cover-agency{font-size:11pt}.cover-school{font-size:13pt}.cover-title{font-size:20pt;line-height:1.45}.cover-number{font-size:12pt}.cover-year{font-size:11pt}.cover-fields{text-align:left;padding:0 14mm;line-height:1.8;font-size:11pt}.cover-fields p{margin:1.5mm 0}.photo-certificate-frame{border-collapse:collapse;border:1.5pt double #000;width:100%;height:245mm;margin-top:3mm}.photo-certificate-frame>tbody>tr>td{vertical-align:top;padding:5mm}.certificate-title{text-align:center;font-size:18pt;font-weight:bold;line-height:1.4;padding-top:50mm;height:105mm;box-sizing:border-box}.photo-certificate{width:100%;margin-top:0}.photo-certificate td{width:50%;vertical-align:top;text-align:left;font-size:9pt;padding:3mm;line-height:1.8}.photo-certificate h3{text-align:center}
 .photo-sign{width:100%;margin-top:10mm}.photo-sign td{width:50%;height:25mm;text-align:center;vertical-align:top;font-size:10pt}.photo-head{width:100%;margin:1mm 0 4mm}.photo-head td{width:50%;text-align:center;vertical-align:top;font-size:9pt}.written{line-height:1.5}.photo-legend{width:100%;font-size:8.5pt;text-align:center;margin:3mm 0}.photo-legend td{width:25%;padding:2mm}.photo-legend .key{border:.5pt solid #000;width:18mm;height:3mm;margin:auto}.lesson-meta{width:100%;margin:6mm 0}.lesson-meta td{width:50%;vertical-align:top;font-size:10pt;line-height:1.8}.lesson-table .photo-grid td{vertical-align:top}.bio-table{width:100%;border-collapse:collapse;margin-bottom:4mm}.bio-table>tbody>tr>td{vertical-align:top}.bio-table p{font-size:8.3pt;line-height:1.35;margin:0 0 1.1mm}.bio-register{width:24%;padding:0 4mm 0 0}.bio-content{width:76%}.registration-frame{width:100%;border-collapse:collapse}.registration-frame td{border:.6pt solid #000;text-align:center;font-size:9pt}.photo-box{width:30mm;height:40mm;border:.6pt solid #000;display:block;margin:0 auto;line-height:40mm;text-align:center}.profile-pair{width:100%;border-collapse:collapse}.profile-pair>tbody>tr>td{width:50%;padding:0;vertical-align:top}.grad-summary{font-size:8pt;line-height:1.7}.grad-summary p{margin:2mm}.usage{font-size:11pt;line-height:1.65;text-align:justify}.usage p{margin:3mm 0}
</style>';
    }
}
