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

        return [
            'filename' => basename((string) $file['name']),
            'sheets' => array_map('count', $sheets),
            'student_sheet' => $studentSheetName,
            'headers' => $headers,
            'student_rows' => array_slice($studentRows, 0, 5000),
            'sheet_data' => $sheetData,
        ];
    }

    private function findStudentSheet(array $sheetNames): ?string
    {
        foreach ($sheetNames as $name) {
            $normalized = Vietnamese::compactKey($name);
            if (in_array($normalized, ['sinhvien', 'hocsinh', 'student', 'students'], true)) {
                return $name;
            }
        }
        return $sheetNames[0] ?? null;
    }
}
