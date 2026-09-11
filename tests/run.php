<?php

declare(strict_types=1);

use AIVANBAN\Services\FormCatalog;
use AIVANBAN\Services\PdfDossierExporter;
use AIVANBAN\Services\StudentMapper;

require dirname(__DIR__) . '/vendor/autoload.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$packages = [
    'so_cap' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
    'trung_cap' => [1, 2, 3, 4, 5, 6, 7, 12, 13, 15, 17],
    'cao_dang' => [1, 2, 3, 4, 5, 6, 7, 12, 14, 16, 18],
];

foreach ($packages as $level => $expectedNumbers) {
    $actualNumbers = array_column(FormCatalog::package($level), 'number');
    $assert($actualNumbers === $expectedNumbers, 'Sai bộ biểu mẫu của ' . $level);
}

$headers = ['MSSV', 'Họ và tên', 'Ngày sinh', 'Lớp', 'Khóa', 'Nghề đào tạo'];
$mapping = StudentMapper::suggest($headers);
$assert($mapping['student_code'] === 0, 'Không nhận diện MSSV.');
$assert($mapping['full_name'] === 1, 'Không nhận diện họ tên.');
$assert($mapping['date_of_birth'] === 2, 'Không nhận diện ngày sinh.');

$result = StudentMapper::mapRows([
    ['SV001', 'Nguyễn Văn An', '01/01/2005', 'TC01', 'K2026', 'Công nghệ thông tin'],
    ['SV001', 'Trần Thị Bình', '02/02/2005', 'TC01', 'K2026', 'Công nghệ thông tin'],
], $headers, $mapping);
$assert(count($result['students']) === 2, 'Sai số lượng sinh viên.');
$assert(count($result['errors']) === 1, 'Không phát hiện đúng mã sinh viên trùng.');

$dateResult = StudentMapper::mapRows([
    ['SV002', 'Lê Thị Hà', 39128, 'TC01', 'K2026', 'Công nghệ thông tin'],
], $headers, $mapping);
$assert($dateResult['students'][0]['date_of_birth'] === '15/02/2007', 'Không đổi đúng ngày Excel sang ngày Việt Nam.');

$inspection = [
    'filename' => 'test.xlsx',
    'headers' => $headers,
    'student_rows' => [['SV002', 'Lê Thị Hà', '01/01/2005', 'TC01', 'K2026', 'Công nghệ thông tin']],
    'sheet_data' => [
        'SinhVien' => [
            'headers' => $headers,
            'rows' => [['SV002', 'Lê Thị Hà', '01/01/2005', 'TC01', 'K2026', 'Công nghệ thông tin']],
        ],
    ],
];
$archive = (new PdfDossierExporter())->buildArchive('trung_cap', $inspection, $mapping);
$assert(is_file($archive), 'Không tạo được gói PDF bản nháp.');
$zip = new ZipArchive();
$assert($zip->open($archive) === true, 'Gói PDF bản nháp không mở được.');
$names = [];
for ($index = 0; $index < $zip->numFiles; $index++) {
    $names[] = $zip->getNameIndex($index);
}
$zip->close();
foreach (['M01-Tien-do-dao-tao.pdf', 'M15-So-quan-ly-hoc-sinh-trung-cap.pdf', 'M17-So-cap-ban-sao-bang-trung-cap.pdf', 'README.txt'] as $expectedName) {
    $assert(in_array($expectedName, $names, true), 'Thiếu file ' . $expectedName . ' trong gói PDF.');
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Tat ca kiem thu deu dat." . PHP_EOL;
