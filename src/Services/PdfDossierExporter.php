<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Vietnamese;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use ZipArchive;

/**
 * Tạo bộ PDF theo cấu trúc các biểu mẫu legacy người dùng cung cấp.
 */
final class PdfDossierExporter
{
    use PhotoFormTemplates;

    private const DEFAULT_SUPERVISORY_BODY = 'SỞ GIÁO DỤC VÀ ĐÀO TẠO TP. CẦN THƠ';
    private const DEFAULT_TRAINING_INSTITUTION = 'TRƯỜNG TRUNG CẤP MIỀN TÂY';

    private const LEVEL_CONFIG = [
        'so_cap' => ['label' => 'Sơ cấp', 'plan' => 8, 'issue' => 9, 'profile' => 11, 'copy' => 10],
        'trung_cap' => ['label' => 'Trung cấp', 'plan' => 12, 'issue' => 13, 'profile' => 15, 'copy' => 17],
        'cao_dang' => ['label' => 'Cao đẳng', 'plan' => 12, 'issue' => 14, 'profile' => 16, 'copy' => 18],
    ];

    public function output(string $level, array $inspection, array $mapping): never
    {
        $archive = $this->buildArchive($level, $inspection, $mapping);
        $filename = 'AIVANBAN-ho-so-' . str_replace('_', '-', $level) . '-' . date('Ymd-His') . '.zip';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        session_write_close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($archive));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($archive);
        $this->removeDirectory(dirname($archive));
        exit;
    }

    public function buildArchive(string $level, array $inspection, array $mapping): string
    {
        $config = self::LEVEL_CONFIG[$level] ?? null;
        if ($config === null) {
            throw new \InvalidArgumentException('Trình độ đào tạo không hợp lệ.');
        }
        if (!class_exists(Mpdf::class)) {
            throw new \RuntimeException('Chưa cài thư viện mPDF. Hãy chạy composer install.');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP chưa bật extension ZIP.');
        }

        $mapped = StudentMapper::mapRows($inspection['student_rows'] ?? [], $inspection['headers'] ?? [], $mapping);
        if ($mapped['errors'] !== [] || $mapped['students'] === []) {
            throw new \RuntimeException('Dữ liệu người học còn lỗi nên chưa thể tạo hồ sơ.');
        }

        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
        $exportRoot = $root . DIRECTORY_SEPARATOR . 'exports';
        $job = $exportRoot . DIRECTORY_SEPARATOR . bin2hex(random_bytes(10));
        $pdfDir = $job . DIRECTORY_SEPARATOR . 'pdf';
        $tempDir = $job . DIRECTORY_SEPARATOR . 'mpdf';
        foreach ([$exportRoot, $job, $pdfDir, $tempDir] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Không thể tạo thư mục tạm để xuất hồ sơ.');
            }
        }

        try {
            $students = $mapped['students'];
            $course = $this->records($inspection, 'ThongTinKhoaHoc')[0] ?? [];
            $sourceRows = array_values(array_filter($inspection['student_rows'] ?? [], static fn (array $row): bool => count(array_filter($row, static fn ($v): bool => trim((string) $v) !== '')) > 0));
            foreach ($students as $i => &$student) {
                $student['_source'] = [];
                foreach ($inspection['headers'] ?? [] as $column => $header) {
                    $student['_source'][Vietnamese::compactKey((string) $header)] = trim((string) ($sourceRows[$i][$column] ?? ''));
                }
            }
            unset($student);
            $course['trinhdo'] = $config['label'];
            foreach (['malop' => 'class_code', 'tenkhoa' => 'course', 'nghedaotao' => 'major'] as $target => $field) {
                $values = array_values(array_unique(array_column($students, $field)));
                if ($this->v($course, $target) === '' && count($values) === 1) $course[$target] = $values[0];
            }
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
            $files = [];

            $files[] = (new TrainingProgressPdf())->render($pdfDir . '/M01-Tien-do-dao-tao.pdf', $tempDir, $this->school($course), $course, $schedule, $students);
            $files[] = $this->renderPdf($pdfDir, $tempDir, 'M02-Ke-hoach-giao-vien.pdf', [297, 210], $this->form02($course, $assignments, $teacherNames, $moduleNames));
            $files[] = $this->renderPdf($pdfDir, $tempDir, 'M03-So-len-lop.pdf', [210, 297], $this->form03($course, $students, $teachers, $schedule, $grades, $attendance));
            $files[] = $this->renderPdf($pdfDir, $tempDir, 'M04-So-tay-giao-vien.pdf', [210, 297], $this->form04($course, $students, $assignments, $teacherNames, $moduleNames, $grades, $attendance));
            $files[] = $this->renderPdf($pdfDir, $tempDir, 'M05-So-giao-an-ly-thuyet.pdf', [210, 297], $this->lessonBook($course, 5, 'LÝ THUYẾT', $assignments, $teacherNames, $moduleNames));
            $files[] = $this->renderPdf($pdfDir, $tempDir, 'M06-So-giao-an-thuc-hanh.pdf', [210, 297], $this->lessonBook($course, 6, 'THỰC HÀNH', $assignments, $teacherNames, $moduleNames));
            $files[] = $this->renderPdf($pdfDir, $tempDir, 'M07-So-giao-an-tich-hop.pdf', [210, 297], $this->lessonBook($course, 7, 'TÍCH HỢP', $assignments, $teacherNames, $moduleNames));

            if ($level === 'so_cap') {
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M08-Ke-hoach-dao-tao-so-cap.pdf', [210, 297], $this->trainingPlan($course, 8, $config['label'], $modules, $schedule));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M09-So-cap-chung-chi-so-cap.pdf', [210, 297], $this->issueRegister($course, 9, 'SỔ CẤP CHỨNG CHỈ SƠ CẤP NGHỀ', 'Học sinh', $students, $graduationByStudent, false));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M10-So-cap-ban-sao-chung-chi.pdf', [210, 297], $this->copyRegister($course, 10, 'SỔ CẤP BẢN SAO CHỨNG CHỈ SƠ CẤP NGHỀ', 'Học sinh', $students, $graduationByStudent));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M11-So-quan-ly-hoc-sinh-so-cap.pdf', [210, 297], $this->profileBook(11, 'SỔ QUẢN LÝ HỌC SINH TRÌNH ĐỘ SƠ CẤP NGHỀ', 'Học sinh', $course, $students, $gradesByStudent, $attendanceByStudent, $graduationByStudent, $moduleNames));
            } elseif ($level === 'trung_cap') {
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M12-Ke-hoach-dao-tao-trung-cap.pdf', [210, 297], $this->trainingPlan($course, 12, $config['label'], $modules, $schedule));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M13-So-cap-bang-tot-nghiep-trung-cap.pdf', [210, 297], $this->issueRegister($course, 13, 'SỔ CẤP BẰNG TỐT NGHIỆP TRUNG CẤP NGHỀ', 'Học sinh', $students, $graduationByStudent, true));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M15-So-quan-ly-hoc-sinh-trung-cap.pdf', [210, 297], $this->profileBook(15, 'SỔ QUẢN LÝ HỌC SINH TRÌNH ĐỘ TRUNG CẤP NGHỀ', 'Học sinh', $course, $students, $gradesByStudent, $attendanceByStudent, $graduationByStudent, $moduleNames));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M17-So-cap-ban-sao-bang-trung-cap.pdf', [210, 297], $this->copyRegister($course, 17, 'SỔ CẤP BẢN SAO BẰNG TỐT NGHIỆP TRUNG CẤP NGHỀ TỪ SỔ GỐC', 'Học sinh', $students, $graduationByStudent));
            } else {
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M12-Ke-hoach-dao-tao-cao-dang.pdf', [210, 297], $this->trainingPlan($course, 12, $config['label'], $modules, $schedule));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M14-So-cap-bang-tot-nghiep-cao-dang.pdf', [210, 297], $this->issueRegister($course, 14, 'SỔ CẤP BẰNG TỐT NGHIỆP CAO ĐẲNG NGHỀ', 'Sinh viên', $students, $graduationByStudent, true));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M16-So-quan-ly-sinh-vien-cao-dang.pdf', [210, 297], $this->profileBook(16, 'SỔ QUẢN LÝ SINH VIÊN TRÌNH ĐỘ CAO ĐẲNG NGHỀ', 'Sinh viên', $course, $students, $gradesByStudent, $attendanceByStudent, $graduationByStudent, $moduleNames));
                $files[] = $this->renderPdf($pdfDir, $tempDir, 'M18-So-cap-ban-sao-bang-cao-dang.pdf', [210, 297], $this->copyRegister($course, 18, 'SỔ CẤP BẢN SAO BẰNG TỐT NGHIỆP CAO ĐẲNG NGHỀ TỪ SỔ GỐC', 'Sinh viên', $students, $graduationByStudent));
            }

            $archive = $job . DIRECTORY_SEPARATOR . 'AIVANBAN-ho-so-' . str_replace('_', '-', $level) . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Không thể tạo file ZIP hồ sơ.');
            }
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->addFromString('README.txt', $this->readme($level, $config['label'], $inspection['filename'] ?? '', count($students)));
            $zip->close();
            return $archive;
        } catch (\Throwable $exception) {
            $this->removeDirectory($job);
            throw $exception;
        }
    }


    private function readme(string $level, string $label, string $filename, int $count): string
    {
        return "AIVANBAN - BO HO SO " . mb_strtoupper($label, 'UTF-8') . "\r\nTrang thai: Da tao tu du lieu Excel; can kiem tra noi dung truoc khi ky.\r\nNguon Excel: " . $filename . "\r\nSo nguoi hoc: " . $count . "\r\nThoi diem tao: " . date('d/m/Y H:i:s') . "\r\n\r\nBo PDF gom bo chung va bo rieng theo trinh do. Truong khong co trong Excel duoc de trong; he thong khong tu bia du lieu.\r\n";
    }

    private function records(array $inspection, string $sheetName): array
    {
        foreach ($inspection['sheet_data'] ?? [] as $name => $sheet) {
            if (Vietnamese::compactKey((string) $name) !== Vietnamese::compactKey($sheetName)) {
                continue;
            }
            $headers = $sheet['headers'] ?? [];
            $records = [];
            foreach ($sheet['rows'] ?? [] as $row) {
                $record = [];
                foreach ($headers as $index => $header) {
                    $record[Vietnamese::compactKey((string) $header)] = trim((string) ($row[$index] ?? ''));
                }
                if (array_filter($record, static fn (string $value): bool => $value !== '') !== []) {
                    $records[] = $record;
                }
            }
            return $records;
        }
        return [];
    }

    private function indexBy(array $records, string $key, string $value): array
    {
        $result = [];
        foreach ($records as $record) {
            $index = $this->v($record, $key);
            if ($index !== '') {
                $result[$index] = $this->v($record, $value);
            }
        }
        return $result;
    }

    private function indexRecords(array $records, string $key): array
    {
        $result = [];
        foreach ($records as $record) {
            $index = $this->v($record, $key);
            if ($index !== '') {
                $result[$index] = $record;
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

    private function school(array $course): string
    {
        return $this->v($course, 'cosodaotao') ?: self::DEFAULT_TRAINING_INSTITUTION;
    }

    private function date(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('d/m/Y');
            } catch (\Throwable) {
                return $value;
            }
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('d/m/Y');
            }
        }
        return $value;
    }

    private function sumAttendance(array $rows, string $key): string
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) $this->v($row, $key);
        }
        return $total === 0.0 ? '' : (string) $total;
    }

    private function blankRow(int $count, string $message): array
    {
        $row = array_fill(0, $count, '');
        $row[max(0, $count - 1)] = $message;
        return $row;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $items = scandir($directory);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }
}
