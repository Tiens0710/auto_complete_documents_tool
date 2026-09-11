<?php
declare(strict_types=1);

namespace AIVANBAN\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/** M01: fixed millimetre geometry, one class per row, never one event per row. */
final class TrainingProgressPdf
{
    public static function model(array $course, array $schedule, array $students): array
    {
        $classes = [];
        foreach ($students as $student) {
            $key = trim((string) ($student['class_code'] ?? ''));
            if ($key !== '') $classes[$key] = [];
        }
        $events = [];
        $visible = array_fill_keys(array_merge(range(1, 6), range(47, 52)), true);
        foreach ($schedule as $i => $row) {
            $week = filter_var($row['tuan'] ?? '', FILTER_VALIDATE_INT);
            $class = trim((string) ($row['malop'] ?? $course['malop'] ?? ''));
            if ($week === false || $week < 1 || $week > 52 || $class === '') {
                throw new \RuntimeException('LichDaoTao dòng ' . ($i + 2) . ': cần mã lớp và tuần nguyên từ 1 đến 52.');
            }
            $id = 'E' . (count($events) + 1);
            $events[$id] = $row + ['malop' => $class];
            $classes[$class][$week][] = $id;
            $visible[$week] = true;
        }
        ksort($visible);
        $columns = [];
        $last = 0;
        foreach (array_keys($visible) as $week) {
            if ($week > $last + 1) $columns[] = null; // Only empty weeks may be abbreviated.
            $columns[] = $week;
            $last = $week;
        }
        return compact('classes', 'events', 'columns');
    }

    private static function date(string $value): string
    {
        if (is_numeric($value)) return Date::excelToDateTimeObject((float) $value)->format('d/m/Y');
        return $value;
    }

    public function render(string $path, string $temp, string $school, array $course, array $schedule, array $students): string
    {
        $model = self::model($course, $schedule, $students);
        $pdf = new Mpdf(['format' => [297, 210], 'tempDir' => $temp, 'default_font' => 'dejavuserif', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12]);
        $pdf->SetTitle('Tiến độ đào tạo');
        $pdf->SetAuthor('AIVANBAN');
        $box = static function (float $x, float $y, float $w, float $h, string $text, float $size = 9, bool $bold = false) use ($pdf): void {
            $html = '<div style="font-family:dejavuserif;color:#000;text-align:center;font-size:' . $size . 'pt;' . ($bold ? 'font-weight:bold;' : '') . '">' . nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</div>';
            $pdf->WriteFixedPosHTML($html, $x, $y, $w, $h, 'auto');
        };
        $classChunks = array_chunk($model['classes'] ?: ['' => []], 5, true);
        $first = true;
        foreach (array_chunk($model['columns'], 18) as $columns) {
            foreach ($classChunks as $classes) {
                if (!$first) $pdf->AddPage();
                $first = false;
                $box(12, 13, 91, 12, $school, 9, true);
                $box(109, 17, 176, 6, 'Ban hành kèm theo Quyết định số 62/2008/QĐ-BLĐTBXH', 8);
                $box(90, 26, 170, 8, 'TIẾN ĐỘ ĐÀO TẠO', 15, true);
                $box(90, 35, 170, 7, 'NĂM HỌC: ' . ($course['namhoc'] ?? '........................'), 11);
                $pdf->SetDrawColor(0, 0, 0);
                $pdf->SetLineWidth(.22);
                $x = 12; $y = 49; $end = 285; $weekX = 88; $noteX = 266;
                $header = 30; $rowH = 7; $bottom = $y + $header + 5 * $rowH;
                $pdf->Rect($x, $y, $end - $x, $bottom - $y);
                foreach ([20, $weekX, $noteX] as $v) $pdf->Line($v, $y, $v, $bottom);
                foreach ([10, 20] as $offset) $pdf->Line($weekX, $y + $offset, $noteX, $y + $offset);
                foreach ([10, 20, 30] as $offset) $pdf->Line(20, $y, $weekX, $y + $offset);
                for ($r = 0; $r < 5; $r++) $pdf->Line($x, $y + $header + $r * $rowH, $end, $y + $header + $r * $rowH);
                $box(12, 52, 8, 10, 'TT', 10, true);
                $box(34, 69, 28, 8, 'LỚP', 11, true);
                $box(72, 50, 15, 5, 'Tháng');
                $box(73, 59, 14, 5, 'Tuần');
                $box(76, 68, 11, 7, "Từ ngày\nđến ngày", 6.5);
                $box(267, 63, 17, 10, 'Ghi chú', 9, true);
                $width = ($noteX - $weekX) / count($columns);
                foreach ($columns as $ci => $week) {
                    $cx = $weekX + $ci * $width;
                    if ($ci) $pdf->Line($cx, $y, $cx, $bottom);
                    $box($cx, 60, $width, 8, $week === null ? '…' : (string) $week, 10);
                    $months = []; $dates = [];
                    foreach ($model['events'] as $event) {
                        if ($week === null || (int) $event['tuan'] !== $week) continue;
                        $start = self::date((string) ($event['tungay'] ?? ''));
                        $finish = self::date((string) ($event['denngay'] ?? ''));
                        if ($start !== '' || $finish !== '') $dates[$start . "\n" . $finish] = true;
                        if (!empty($event['thang'])) $months[(string) $event['thang']] = true;
                        elseif (preg_match('~^\d{2}/(\d{2})/(\d{4})$~', $start, $m)) $months[$m[1] . '/' . $m[2]] = true;
                    }
                    $box($cx + .3, 51, $width - .6, 7, implode(', ', array_keys($months)), 7);
                    // Keep the actual supplied ranges in the header cell. Never
                    // point to an appendix that is not printed in this form.
                    $box($cx + .3, 70, $width - .6, 8, implode("\n", array_keys($dates)) ?: ($week === null ? '…' : ''), 5.5);
                }
                $ri = 0;
                foreach ($classes as $class => $weeks) {
                    $ry = $y + $header + $ri * $rowH + 1;
                    $box(12, $ry, 8, 5, $class === '' ? '' : (string) (array_search($class, array_keys($model['classes']), true) + 1));
                    $box(21, $ry, 66, 5, (string) $class);
                    foreach ($columns as $ci => $week) {
                        $ids = $week === null ? [] : ($weeks[$week] ?? []);
                        // Put the supplied schedule content in the cell.  E1/E2
                        // references are useful for diagnostics but are not part
                        // of Mẫu 1 and must never appear on the printed form.
                        $labels = [];
                        foreach ($ids as $id) {
                            $event = $model['events'][$id] ?? [];
                            $content = trim((string) ($event['noidung'] ?? ''));
                            if ($content === '') $content = trim((string) ($event['loaihoatdong'] ?? ''));
                            if ($content !== '') $labels[] = $content;
                        }
                        $label = implode("\n", array_values(array_unique($labels)));
                        $box($weekX + $ci * $width + .3, $ry, $width - .6, 5, $label, 7);
                    }
                    $noteLabels = [];
                    foreach ($weeks as $weekEvents) foreach ($weekEvents as $id) {
                        $note = trim((string) ($model['events'][$id]['ghichu'] ?? ''));
                        if ($note !== '') $noteLabels[] = $note;
                    }
                    $box(267, $ry, 17, 5, implode("\n", array_unique($noteLabels)), 6);
                    $ri++;
                }
                $labels = ['Khai bế giảng', 'Văn hoá THPT', 'Môn chung', "Môn học /mô-đun\nđào tạo nghề", "Thi tốt nghiệp (kiểm tra\nkết thúc khoá học)", 'Nghỉ hè, lễ', 'Lao động/ngoại khoá', "Thực tập tại doanh\nnghiệp"];
                foreach ($labels as $i => $label) {
                    $lx = 12 + ($i % 4) * 68.25; $ly = 119 + intdiv($i, 4) * 20;
                    $pdf->Rect($lx + 25, $ly, 18, 3.5);
                    $box($lx, $ly + 5, 68.25, 12, $label, 9);
                }
                $box(12, 160, 273, 7, 'Ghi chú: Các cơ sở quy định các ký hiệu cụ thể cho từng nội dung sao cho không trùng lặp.', 9);
                $box(25, 172, 110, 6, 'HIỆU TRƯỞNG/ GIÁM ĐỐC', 11, true);
                $box(25, 180, 110, 6, '(Ký tên, đóng dấu)', 10);
                $box(165, 172, 115, 6, 'TRƯỞNG PHÒNG ĐÀO TẠO', 11, true);
            }
        }
        $pdf->Output($path, Destination::FILE);
        return $path;
    }
}
