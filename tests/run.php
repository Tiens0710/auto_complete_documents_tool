<?php

declare(strict_types=1);

use AIVANBAN\Services\FormCatalog;
use AIVANBAN\Services\PdfDossierExporter;
use AIVANBAN\Services\SpreadsheetImporter;
use AIVANBAN\Services\StudentMapper;
use AIVANBAN\Services\TemplateGenerator;

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

$oneSheetHeaders = [
    'TT', 'Họ đệm', 'Tên', 'Giới tính', 'Ngày sinh', 'Nơi sinh', 'Số CCCD', 'Ngày cấp',
    'Số điện thoại', 'Dân tộc', 'Tôn giáo', 'Quốc tịch', 'Ngày nhập học', 'Ngày tốt nghiệp',
    'Xếp loại', 'Số hiệu văn bằng', 'Ngày cấp văn bằng', 'Số vào sổ cấp bằng',
    'Địa chỉ liên hệ', 'Điểm thi lý thuyết', 'Điểm thi thực hành', 'ĐTKKH',
];
$templateSheets = (new ReflectionClass(TemplateGenerator::class))->getReflectionConstant('SHEETS')->getValue();
$templateHeaders = $templateSheets['SinhVien'] ?? [];
$assert(count($templateSheets) === 1, 'File Excel mẫu phải chỉ có một sheet.');
$assert($templateHeaders === $oneSheetHeaders, 'Tiêu đề file Excel mẫu không khớp 22 cột người dùng cung cấp.');
$oneSheetMapping = StudentMapper::suggest($oneSheetHeaders);
$assert($oneSheetMapping['student_code'] === 0, 'Không nhận diện TT làm mã dòng người học.');
$assert($oneSheetMapping['middle_name'] === 1 && $oneSheetMapping['given_name'] === 2, 'Không nhận diện hai cột họ đệm/tên.');
$assert($oneSheetMapping['date_of_birth'] === 4, 'Không nhận diện ngày sinh theo mẫu một sheet.');
$oneSheetResult = StudentMapper::mapRows([[
    '1', 'Nguyễn Hoàng', 'An', 'Nam', '15/11/2004', 'Cần Thơ', '000000000001', '10/07/2023',
    '0900000001', 'Kinh', '', 'Việt Nam', '', '2026', '', 'BD0001', '10/06/2026',
    '001/TEST', 'Địa chỉ giả, Cần Thơ', '6.9', '8.0', '7.56',
]], $oneSheetHeaders, $oneSheetMapping);
$oneSheetStudent = $oneSheetResult['students'][0] ?? [];
$assert($oneSheetResult['errors'] === [], 'Mẫu một sheet không được đòi Lớp/Khóa/Nghề hoặc cột họ tên gộp.');
$assert(($oneSheetStudent['full_name'] ?? '') === 'Nguyễn Hoàng An', 'Không ghép đúng Họ đệm và Tên.');
$assert(($oneSheetStudent['class_code'] ?? null) === '' && ($oneSheetStudent['course'] ?? null) === '' && ($oneSheetStudent['major'] ?? null) === '', 'Thông tin không có trong mẫu phải để trống.');
$assert(($oneSheetStudent['contact_address'] ?? '') === 'Địa chỉ giả, Cần Thơ' && ($oneSheetStudent['permanent_address'] ?? '') === '', 'Địa chỉ liên hệ không được hiểu nhầm thành địa chỉ thường trú.');
$assert(($oneSheetStudent['graduation_year'] ?? '') === '2026', 'Năm tốt nghiệp phải được giữ nguyên, không đổi thành ngày Excel.');
$assert(($oneSheetStudent['diploma_number'] ?? '') === 'BD0001' && ($oneSheetStudent['diploma_register_number'] ?? '') === '001/TEST', 'Không nhận diện thông tin văn bằng.');
$assert(($oneSheetStudent['citizen_id_issue_date'] ?? '') === '10/07/2023', 'Không nhận diện Ngày cấp CCCD trong biểu mẫu người dùng.');

$graduationSheet = SpreadsheetImporter::buildGraduationSheet($oneSheetHeaders, [[
    '1', 'Nguyễn Hoàng', 'An', 'Nam', '15/11/2004', 'Cần Thơ', '000000000001', '10/07/2023',
    '0900000001', 'Kinh', '', 'Việt Nam', '', '2026', 'Khá', 'BD0001', '10/06/2026',
    '001/TEST', 'Địa chỉ giả, Cần Thơ', '6.9', '8.0', '7.56',
]]);
$assert(($graduationSheet['headers'][0] ?? '') === 'MSSV', 'Không tạo được bảng tốt nghiệp liên kết bằng TT.');
$assert(($graduationSheet['rows'][0][2] ?? '') === 'BD0001' && ($graduationSheet['rows'][0][3] ?? '') === '10/06/2026', 'Không chuyển đúng số hiệu/ngày cấp bằng sang bảng tốt nghiệp.');
$assert(SpreadsheetImporter::buildGraduationSheet(['MSSV', 'Họ và tên', 'Ngày sinh'], []) === null, 'Không được tự tạo bảng tốt nghiệp cho định dạng Excel khác.');

if (($argv[1] ?? '') === '--import-format-only') {
    if ($failures !== []) {
        fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
        exit(1);
    }
    echo "Kiem thu dinh dang nhap Excel deu dat." . PHP_EOL;
    exit(0);
}

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

$singleArchive = (new PdfDossierExporter())->buildArchive('trung_cap', $inspection, $mapping, 13);
$assert(is_file($singleArchive), 'Không tạo được gói tạm cho biểu mẫu tải riêng.');
$singleZip = new ZipArchive();
$assert($singleZip->open($singleArchive) === true, 'Gói tạm của biểu mẫu tải riêng không mở được.');
$singlePdfNames = [];
for ($index = 0; $index < $singleZip->numFiles; $index++) {
    $name = $singleZip->getNameIndex($index);
    if (is_string($name) && str_ends_with($name, '.pdf')) {
        $singlePdfNames[] = $name;
    }
}
$singleZip->close();
$assert($singlePdfNames === ['M13-So-cap-bang-tot-nghiep-trung-cap.pdf'], 'Tải riêng phải chỉ tạo đúng PDF đã chọn.');

try {
    (new PdfDossierExporter())->buildArchive('trung_cap', $inspection, $mapping, 14);
    $assert(false, 'Không được xuất biểu mẫu cao đẳng với trình độ trung cấp.');
} catch (InvalidArgumentException) {
    // The selected form number must belong to the chosen level.
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Tat ca kiem thu deu dat." . PHP_EOL;
