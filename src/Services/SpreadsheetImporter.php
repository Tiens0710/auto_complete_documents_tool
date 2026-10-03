<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Vietnamese;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class SpreadsheetImporter
{
    private const MAX_SIZE = 10 * 1024 * 1024;
    private const ALLOWED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function inspectUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Không nhận được file Excel hợp lệ.');
        }
        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::MAX_SIZE) {
            throw new \RuntimeException('File phải có dung lượng từ 1 byte đến 10 MB.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \RuntimeException('Chỉ chấp nhận file .xlsx, .xls hoặc .csv.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            throw new \RuntimeException('Nguồn file tải lên không hợp lệ.');
        }

        $reader = IOFactory::createReaderForFile($tmpName);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tmpName);

        $sheets = [];
        $sheetData = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $rows = $worksheet->toArray(null, true, true, false);
            while ($rows !== [] && count(array_filter(end($rows), static fn ($value): bool => trim((string) $value) !== '')) === 0) {
                array_pop($rows);
            }
            $title = $worksheet->getTitle();
            $sheets[$title] = $rows;
            $sheetRows = $rows;
            $sheetHeaders = array_map(static fn ($value): string => trim((string) $value), array_shift($sheetRows) ?? []);
            $sheetData[$title] = [
                'headers' => $sheetHeaders,
                'rows' => array_slice($sheetRows, 0, 5000),
            ];
        }

        $studentSheetName = $this->findStudentSheet(array_keys($sheets));
        $studentRows = $studentSheetName !== null ? ($sheets[$studentSheetName] ?? []) : [];
        $headers = array_map(static fn ($value): string => trim((string) $value), array_shift($studentRows) ?? []);

        if ($studentSheetName !== null) {
            if (Vietnamese::compactKey($studentSheetName) !== Vietnamese::compactKey('SinhVien')) {
                $sheets['SinhVien'] = $sheets[$studentSheetName];
                $sheetData['SinhVien'] = $sheetData[$studentSheetName];
            }

            $graduationData = self::buildGraduationSheet($headers, $studentRows);
            $hasGraduationSheet = false;
            foreach (array_keys($sheetData) as $name) {
                if (Vietnamese::compactKey($name) === Vietnamese::compactKey('TotNghiepCapBang')) {
                    $hasGraduationSheet = true;
                    break;
                }
            }
            if ($graduationData !== null && !$hasGraduationSheet) {
                $sheetData['TotNghiepCapBang'] = $graduationData;
                $sheets['TotNghiepCapBang'] = [$graduationData['headers'], ...$graduationData['rows']];
            }
        }

        return [
            'filename' => basename((string) $file['name']),
            'sheets' => array_map('count', $sheets),
            'student_sheet' => $studentSheetName,
            'headers' => $headers,
            'student_rows' => array_slice($studentRows, 0, 5000),
            'sheet_data' => $sheetData,
        ];
    }

    /**
     * Builds only the graduation fields represented explicitly in the one-sheet student format.
     * Exam summary scores stay labeled as source data and are not expanded into module grades.
     */
    public static function buildGraduationSheet(array $headers, array $rows): ?array
    {
        $columns = [];
        foreach ($headers as $index => $header) {
            $columns[Vietnamese::compactKey((string) $header)] = $index;
        }

        foreach (['tt', 'hodem', 'ten', 'ngaysinh'] as $requiredHeader) {
            if (!array_key_exists($requiredHeader, $columns)) {
                return null;
            }
        }

        $fields = [
            'MSSV' => 'tt',
            'Xếp loại tốt nghiệp' => 'xeploai',
            'Số hiệu bằng/chứng chỉ' => 'sohieuvanbang',
            'Ngày cấp' => 'ngaycapvanbang',
            'Số vào sổ cấp bằng' => 'sovaosocapbang',
            'Ngày tốt nghiệp' => 'ngaytotnghiep',
            'Điểm thi lý thuyết' => 'diemthilythuyet',
            'Điểm thi thực hành' => 'diemthithuchanh',
            'ĐTKKH' => 'dtkkh',
        ];
        $graduationIndexes = array_intersect_key($columns, array_flip(array_slice(array_values($fields), 1)));
        if ($graduationIndexes === []) {
            return null;
        }

        $records = [];
        foreach (array_slice($rows, 0, 5000) as $row) {
            $record = [];
            foreach ($fields as $target => $source) {
                $index = $columns[$source] ?? null;
                $record[] = $index === null ? '' : trim((string) ($row[$index] ?? ''));
            }
            if ($record[0] === '' || count(array_filter(array_slice($record, 1), static fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }
            $records[] = $record;
        }

        return $records === [] ? null : ['headers' => array_keys($fields), 'rows' => $records];
    }

    private function findStudentSheet(array $sheetNames): ?string
    {
        foreach ($sheetNames as $name) {
            if (Vietnamese::compactKey($name) === Vietnamese::compactKey('SinhVien')) {
                return $name;
            }
        }
        foreach ($sheetNames as $name) {
            $normalized = Vietnamese::compactKey($name);
            if (in_array($normalized, ['hocsinh', 'student', 'students'], true)) {
                return $name;
            }
        }
        return $sheetNames[0] ?? null;
    }
}
