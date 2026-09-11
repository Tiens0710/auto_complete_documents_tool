<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Vietnamese;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class DossierExporter
{
    private const LEVEL_CONFIG = [
        'so_cap' => ['label' => 'Sơ cấp', 'plan' => 8, 'issue' => 9, 'profile' => 11, 'copy' => 10],
        'trung_cap' => ['label' => 'Trung cấp', 'plan' => 12, 'issue' => 13, 'profile' => 15, 'copy' => 17],
        'cao_dang' => ['label' => 'Cao đẳng', 'plan' => 12, 'issue' => 14, 'profile' => 16, 'copy' => 18],
    ];

    public function output(string $level, array $inspection, array $mapping): never
    {
        $config = self::LEVEL_CONFIG[$level] ?? null;
        if ($config === null) {
            throw new \InvalidArgumentException('Trình độ không hợp lệ.');
        }

        $mapped = StudentMapper::mapRows($inspection['student_rows'] ?? [], $inspection['headers'] ?? [], $mapping);
        if ($mapped['errors'] !== [] || $mapped['students'] === []) {
            throw new \RuntimeException('Dữ liệu người học còn lỗi nên chưa thể tạo hồ sơ.');
        }

        $students = $mapped['students'];
        $course = $this->records($inspection, 'ThongTinKhoaHoc')[0] ?? [];
        $classes = $this->records($inspection, 'LopHoc');
        $teachers = $this->records($inspection, 'GiaoVien');
        $modules = $this->records($inspection, 'MonHocMoDun');
        $assignments = $this->records($inspection, 'PhanCongGiangDay');
        $schedule = $this->records($inspection, 'LichDaoTao');
        $grades = $this->records($inspection, 'Diem');
        $attendance = $this->records($inspection, 'ChuyenCan');
        $graduation = $this->records($inspection, 'TotNghiepCapBang');

        $teacherNames = $this->indexBy($teachers, 'magiaovien', 'hovaten');
        $moduleNames = $this->indexBy($modules, 'mamonmodun', 'tenmonmodun');
        $graduationByStudent = $this->indexRecords($graduation, 'mssv');
        $gradesByStudent = $this->groupBy($grades, 'mssv');
        $attendanceByStudent = $this->groupBy($attendance, 'mssv');

        $workbook = new Spreadsheet();
        $workbook->getProperties()
            ->setCreator('AIVANBAN')
            ->setTitle('Bộ hồ sơ ' . $config['label'])
            ->setDescription('Tạo từ dữ liệu Excel; cần kiểm tra nội dung trước khi ký hoặc sử dụng chính thức.');

        $guide = $workbook->getActiveSheet();
        $guide->setTitle('HuongDan');
        $this->buildGuide($guide, $config, $inspection, count($students));

        $this->addTableSheet($workbook, 'M01_TienDo', 'TIẾN ĐỘ ĐÀO TẠO',
            ['Mã lớp', 'Tuần', 'Từ ngày', 'Đến ngày', 'Nội dung', 'Loại hoạt động'],
            array_map(fn (array $row): array => [
                $this->v($row, 'malop'), $this->v($row, 'tuan'), $this->date($this->v($row, 'tungay')),
                $this->date($this->v($row, 'denngay')), $this->v($row, 'noidung'), $this->v($row, 'loaihoatdong'),
            ], $schedule));

        $assignmentRows = array_map(fn (array $row): array => [
            $this->v($row, 'magiaovien'), $teacherNames[$this->v($row, 'magiaovien')] ?? '',
            $this->v($row, 'malop'), $this->v($row, 'mamonmodun'), $moduleNames[$this->v($row, 'mamonmodun')] ?? '',
            $this->date($this->v($row, 'tungay')), $this->date($this->v($row, 'denngay')), $this->v($row, 'sogio'),
        ], $assignments);
        $this->addTableSheet($workbook, 'M02_KeHoachGV', 'KẾ HOẠCH GIÁO VIÊN',
            ['Mã GV', 'Giáo viên', 'Mã lớp', 'Mã MH/MĐ', 'Môn học/mô-đun', 'Từ ngày', 'Đến ngày', 'Số giờ'], $assignmentRows);

        $attendanceTotals = [];
        foreach ($attendance as $row) {
            $code = $this->v($row, 'mssv');
            $attendanceTotals[$code]['allowed'] = ($attendanceTotals[$code]['allowed'] ?? 0) + (float) $this->v($row, 'sogionghicophep');
            $attendanceTotals[$code]['unallowed'] = ($attendanceTotals[$code]['unallowed'] ?? 0) + (float) $this->v($row, 'sogionghikhongphep');
        }
        $classRows = array_map(fn (array $student): array => [
            $student['student_code'], $student['full_name'], $student['date_of_birth'], $student['class_code'],
            $attendanceTotals[$student['student_code']]['allowed'] ?? 0,
            $attendanceTotals[$student['student_code']]['unallowed'] ?? 0,
        ], $students);
        $this->addTableSheet($workbook, 'M03_SoLenLop', 'SỔ LÊN LỚP',
            ['MSSV', 'Họ và tên', 'Ngày sinh', 'Lớp', 'Giờ nghỉ có phép', 'Giờ nghỉ không phép'], $classRows);

        $this->addTableSheet($workbook, 'M04_SoTayGV', 'SỔ TAY GIÁO VIÊN',
            ['Mã GV', 'Giáo viên', 'Lớp', 'Mã MH/MĐ', 'Môn học/mô-đun', 'Số giờ'],
            array_map(static fn (array $row): array => [$row[0], $row[1], $row[2], $row[3], $row[4], $row[7]], $assignmentRows));

        foreach ([5 => 'Lý thuyết', 6 => 'Thực hành', 7 => 'Tích hợp'] as $number => $kind) {
            $this->addTableSheet($workbook, 'M0' . $number . '_GiaoAn', 'GIÁO ÁN ' . mb_strtoupper($kind, 'UTF-8'),
                ['Giáo viên', 'Môn học/mô-đun', 'Lớp', 'Từ ngày', 'Đến ngày', 'Số giờ', 'Tên bài/mục tiêu/nội dung'],
                array_map(static fn (array $row): array => [$row[1], $row[4], $row[2], $row[5], $row[6], $row[7], 'Cần bổ sung trong dữ liệu giáo án'], $assignmentRows));
        }

        $planNumber = $config['plan'];
        $this->addTableSheet($workbook, 'M' . str_pad((string) $planNumber, 2, '0', STR_PAD_LEFT) . '_KeHoachDT',
            'KẾ HOẠCH ĐÀO TẠO ' . mb_strtoupper($config['label'], 'UTF-8'),
            ['Mã MH/MĐ', 'Tên môn học/mô-đun', 'Lý thuyết', 'Thực hành', 'Kiểm tra', 'Tổng giờ'],
            array_map(fn (array $row): array => [
                $this->v($row, 'mamonmodun'), $this->v($row, 'tenmonmodun'), $this->v($row, 'lythuyet'),
                $this->v($row, 'thuchanh'), $this->v($row, 'kiemtra'),
                (float) $this->v($row, 'lythuyet') + (float) $this->v($row, 'thuchanh') + (float) $this->v($row, 'kiemtra'),
            ], $modules), $course);

        $issueRows = [];
        $copyRows = [];
        foreach ($students as $student) {
            $graduate = $graduationByStudent[$student['student_code']] ?? [];
            $issueRows[] = [
                $student['student_code'], $student['full_name'], $student['date_of_birth'], $student['place_of_birth'],
                $student['major'], $student['course'], $this->v($graduate, 'soquyetdinh'),
                $this->date($this->v($graduate, 'ngayquyetdinh')), $this->v($graduate, 'xeploaitotnghiep'),
                $this->v($graduate, 'sohieubangchungchi'), $this->date($this->v($graduate, 'ngaycap')),
                $this->date($this->v($graduate, 'ngaynhan')),
            ];
            $copyRows[] = [
                $student['student_code'], $student['full_name'], $student['date_of_birth'], $student['place_of_birth'],
                $this->v($graduate, 'soquyetdinh'), $this->v($graduate, 'sohieubangchungchi'), '', '', '', '',
            ];
        }

        $issueNumber = $config['issue'];
        $this->addTableSheet($workbook, 'M' . $issueNumber . '_SoCapBang', 'SỔ CẤP BẰNG/CHỨNG CHỈ',
            ['MSSV', 'Họ và tên', 'Ngày sinh', 'Nơi sinh', 'Nghề/ngành', 'Khóa', 'Số quyết định', 'Ngày quyết định', 'Xếp loại', 'Số hiệu', 'Ngày cấp', 'Ngày nhận'], $issueRows);

        $copyNumber = $config['copy'];
        $this->addTableSheet($workbook, 'M' . $copyNumber . '_SoBanSao', 'SỔ CẤP BẢN SAO TỪ SỔ GỐC',
            ['MSSV', 'Họ và tên', 'Ngày sinh', 'Nơi sinh', 'Số quyết định', 'Số hiệu', 'Số sổ/trang', 'Hình thức cấp', 'Ngày cấp bản sao', 'Số lượng'], $copyRows);

        foreach ($students as $index => $student) {
            $profileNumber = $config['profile'];
            $sheet = $workbook->createSheet();
            $sheet->setTitle('M' . $profileNumber . '_' . substr($student['student_code'], 0, 20));
            $this->buildStudentProfile(
                $sheet,
                $profileNumber,
                $config['label'],
                $student,
                $gradesByStudent[$student['student_code']] ?? [],
                $attendanceByStudent[$student['student_code']] ?? [],
                $graduationByStudent[$student['student_code']] ?? [],
                $moduleNames,
                $index + 1
            );
        }

        $filename = 'AIVANBAN-ho-so-' . str_replace('_', '-', $level) . '-' . date('Ymd-His') . '.xlsx';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        session_write_close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-store');
        (new Xlsx($workbook))->save('php://output');
        exit;
    }

    private function buildGuide(Worksheet $sheet, array $config, array $inspection, int $studentCount): void
    {
        $sheet->setCellValue('A1', 'AIVANBAN - BỘ HỒ SƠ ' . mb_strtoupper($config['label'], 'UTF-8'));
        $sheet->setCellValue('A3', 'Trạng thái');
        $sheet->setCellValue('B3', 'Đã tạo từ dữ liệu Excel; kiểm tra nội dung trước khi ký.');
        $sheet->setCellValue('A4', 'File nguồn');
        $sheet->setCellValueExplicit('B4', (string) ($inspection['filename'] ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue('A5', 'Số người học');
        $sheet->setCellValue('B5', $studentCount);
        $sheet->setCellValue('A6', 'Ngày tạo');
        $sheet->setCellValue('B6', date('d/m/Y H:i'));
        $sheet->setCellValue('A8', 'Lưu ý');
        $sheet->setCellValue('B8', 'Các trường không có trong Excel được để trống hoặc ghi rõ cần bổ sung; hệ thống không tự bịa nội dung.');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF185C53');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A3:A8')->getFont()->setBold(true);
        $sheet->getStyle('B3')->getFont()->setBold(true)->getColor()->setARGB('FF000000');
        $sheet->getStyle('A3:B8')->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(72);
        $sheet->setShowGridlines(false);
    }

    private function addTableSheet(Spreadsheet $workbook, string $sheetName, string $title, array $headers, array $rows, array $course = []): void
    {
        $sheet = $workbook->createSheet();
        $sheet->setTitle(substr($sheetName, 0, 31));
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $subtitle = 'Dữ liệu nguồn: Excel đã nhập';
        if ($course !== []) {
            $subtitle .= ' | Khóa: ' . $this->v($course, 'tenkhoa') . ' | Nghề: ' . $this->v($course, 'nghedaotao');
        }
        $sheet->setCellValue('A2', $subtitle);

        foreach ($headers as $column => $header) {
            $sheet->setCellValue([$column + 1, 4], $header);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $column => $value) {
                $cell = $sheet->getCell([$column + 1, $rowIndex + 5]);
                if (is_int($value) || is_float($value)) {
                    $cell->setValue($value);
                } else {
                    $cell->setValueExplicit($this->safeText((string) $value), DataType::TYPE_STRING);
                }
            }
        }

        $this->styleSheet($sheet, count($headers), max(count($rows) + 4, 5));
    }

    private function buildStudentProfile(Worksheet $sheet, int $number, string $levelLabel, array $student, array $grades, array $attendance, array $graduate, array $moduleNames, int $position): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'HỒ SƠ NGƯỜI HỌC ' . mb_strtoupper($levelLabel, 'UTF-8'));
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Người học số ' . $position . ' trong file nhập');

        $fields = [
            ['Mã học sinh/sinh viên', $student['student_code'], 'Họ và tên', $student['full_name']],
            ['Ngày sinh', $student['date_of_birth'], 'Giới tính', $student['gender']],
            ['Nơi sinh', $student['place_of_birth'], 'Quê quán', $student['hometown']],
            ['Thường trú', $student['permanent_address'], 'Điện thoại', $student['phone']],
            ['Dân tộc', $student['ethnicity'], 'Tôn giáo', $student['religion']],
            ['Lớp', $student['class_code'], 'Khóa', $student['course']],
            ['Nghề/ngành đào tạo', $student['major'], 'Số quyết định tốt nghiệp', $this->v($graduate, 'soquyetdinh')],
            ['Ngày quyết định', $this->date($this->v($graduate, 'ngayquyetdinh')), 'Xếp loại tốt nghiệp', $this->v($graduate, 'xeploaitotnghiep')],
            ['Số hiệu bằng/chứng chỉ', $this->v($graduate, 'sohieubangchungchi'), 'Ngày cấp', $this->date($this->v($graduate, 'ngaycap'))],
        ];
        $row = 4;
        foreach ($fields as [$label1, $value1, $label2, $value2]) {
            $sheet->setCellValue('A' . $row, $label1);
            $sheet->setCellValueExplicit('B' . $row, $this->safeText((string) $value1), DataType::TYPE_STRING);
            $sheet->mergeCells('B' . $row . ':C' . $row);
            $sheet->setCellValue('D' . $row, $label2);
            $sheet->setCellValueExplicit('E' . $row, $this->safeText((string) $value2), DataType::TYPE_STRING);
            $sheet->mergeCells('E' . $row . ':F' . $row);
            $row++;
        }

        $row++;
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'KẾT QUẢ HỌC TẬP');
        $gradeHeaderRow = ++$row;
        $gradeHeaders = ['Mã MH/MĐ', 'Tên môn học/mô-đun', 'Định kỳ 1', 'Định kỳ 2', 'Kết thúc lần 1', 'Tổng kết'];
        foreach ($gradeHeaders as $column => $header) {
            $sheet->setCellValue([$column + 1, $gradeHeaderRow], $header);
        }
        foreach ($grades as $grade) {
            $row++;
            $moduleCode = $this->v($grade, 'mamonmodun');
            $values = [$moduleCode, $moduleNames[$moduleCode] ?? '', $this->v($grade, 'diemdinhky1'), $this->v($grade, 'diemdinhky2'), $this->v($grade, 'diemketthuclan1'), $this->v($grade, 'diemtongket')];
            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $row], $this->safeText((string) $value), DataType::TYPE_STRING);
            }
        }

        $row += 2;
        $sheet->setCellValue('A' . $row, 'CHUYÊN CẦN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Tổng giờ nghỉ có phép');
        $sheet->setCellValue('B' . $row, array_sum(array_map(fn (array $item): float => (float) $this->v($item, 'sogionghicophep'), $attendance)));
        $sheet->setCellValue('D' . $row, 'Tổng giờ nghỉ không phép');
        $sheet->setCellValue('E' . $row, array_sum(array_map(fn (array $item): float => (float) $this->v($item, 'sogionghikhongphep'), $attendance)));

        $this->styleProfileSheet($sheet, $gradeHeaderRow, $row);
        $sheet->getStyle('A4:A12')->getFont()->setBold(true);
        $sheet->getStyle('D4:D12')->getFont()->setBold(true);
        $sheet->getStyle('A' . ($gradeHeaderRow - 1) . ':F' . ($gradeHeaderRow - 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDDECE7');
        $sheet->getStyle('A' . ($gradeHeaderRow - 1))->getFont()->setBold(true)->getColor()->setARGB('FF185C53');
        $sheet->getStyle('A' . $gradeHeaderRow . ':F' . $gradeHeaderRow)->applyFromArray($this->headerStyle());
    }

    private function styleProfileSheet(Worksheet $sheet, int $gradeHeaderRow, int $lastRow): void
    {
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A' . ($gradeHeaderRow + 1));
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getStyle('A1:F' . $lastRow)->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF185C53');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FFC44F2B');
        $sheet->getStyle('A1:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:F12')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD7E0DC');
        $sheet->getStyle('A' . $gradeHeaderRow . ':F' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD7E0DC');
        $sheet->getStyle('A4:F' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        foreach (['A' => 22, 'B' => 18, 'C' => 16, 'D' => 23, 'E' => 18, 'F' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension($gradeHeaderRow)->setRowHeight(32);
    }

    private function styleSheet(Worksheet $sheet, int $columnCount, int $lastRow): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A5');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF185C53');
        $sheet->getStyle('A1:' . $lastColumn . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setARGB('FFC44F2B');
        $sheet->getStyle('A4:' . $lastColumn . '4')->applyFromArray($this->headerStyle());
        $sheet->getStyle('A4:' . $lastColumn . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle('A4:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD7E0DC');
        $sheet->setAutoFilter('A4:' . $lastColumn . max(4, $lastRow));
        for ($column = 1; $column <= $columnCount; $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(4)->setRowHeight(32);
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF185C53']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
    }

    private function records(array $inspection, string $sheetName): array
    {
        $sheet = $inspection['sheet_data'][$sheetName] ?? null;
        if (!is_array($sheet)) {
            return [];
        }
        $headers = array_map(static fn ($value): string => Vietnamese::compactKey((string) $value), $sheet['headers'] ?? []);
        $records = [];
        foreach ($sheet['rows'] ?? [] as $row) {
            if (count(array_filter($row, static fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $record = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $record[$header] = $row[$index] ?? '';
                }
            }
            $records[] = $record;
        }
        return $records;
    }

    private function indexBy(array $records, string $key, string $value): array
    {
        $result = [];
        foreach ($records as $record) {
            if ($this->v($record, $key) !== '') {
                $result[$this->v($record, $key)] = $this->v($record, $value);
            }
        }
        return $result;
    }

    private function indexRecords(array $records, string $key): array
    {
        $result = [];
        foreach ($records as $record) {
            if ($this->v($record, $key) !== '') {
                $result[$this->v($record, $key)] = $record;
            }
        }
        return $result;
    }

    private function groupBy(array $records, string $key): array
    {
        $result = [];
        foreach ($records as $record) {
            $result[$this->v($record, $key)][] = $record;
        }
        return $result;
    }

    private function v(array $record, string $key): string
    {
        return trim((string) ($record[Vietnamese::compactKey($key)] ?? ''));
    }

    private function date(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('d/m/Y');
        }
        return $value;
    }

    private function safeText(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    }
}
