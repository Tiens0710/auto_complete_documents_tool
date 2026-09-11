<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use AIVANBAN\Services\TrainingProgressPdf;
use AIVANBAN\Support\Vietnamese;
function check(bool $ok): void { if (!$ok) throw new RuntimeException('M01 regression failed'); }
$rows = [['malop'=>'TC1','tuan'=>1], ['malop'=>'TC1','tuan'=>1], ['malop'=>'TC1','tuan'=>26], ['malop'=>'TC2','tuan'=>52]];
$m = TrainingProgressPdf::model([], $rows, []);
check(count($m['classes']) === 2);
check(count($m['classes']['TC1'][1]) === 2);
check(in_array(26, $m['columns'], true));
check(count($m['events']) === 4);
foreach ([0, 53, '1.5', 'abc'] as $week) {
    try { TrainingProgressPdf::model([], [['malop'=>'TC1','tuan'=>$week]], []); throw new LogicException('Accepted invalid week'); }
    catch (RuntimeException $e) {}
}
$root = dirname(__DIR__);
$files = glob($root . '/outputs/*/AIVANBAN-test-trung-cap.xlsx');
if (!$files) throw new RuntimeException('Missing sample workbook');
$book = \PhpOffice\PhpSpreadsheet\IOFactory::load($files[0]);
$records = static function (string $name) use ($book): array {
    $sheet = $book->getSheetByName($name);
    if (!$sheet) return [];
    $data = $sheet->toArray('', true, false);
    $headers = array_map(static fn ($v) => Vietnamese::compactKey((string) $v), array_shift($data));
    $result = [];
    foreach ($data as $row) {
        if (!array_filter($row, static fn ($v) => (string) $v !== '')) continue;
        $record = [];
        foreach ($headers as $i => $key) $record[$key] = (string) ($row[$i] ?? '');
        $result[] = $record;
    }
    return $result;
};
foreach (['output/pdf','tmp/pdfs/mpdf'] as $dir) if (!is_dir($root . '/' . $dir)) mkdir($root . '/' . $dir, 0775, true);
$course = $records('ThongTinKhoaHoc')[0] ?? [];
$schedule = $records('LichDaoTao');
(new TrainingProgressPdf())->render($root . '/output/pdf/M01-Tien-do-dao-tao-doi-chieu.pdf', $root . '/tmp/pdfs/mpdf', 'TRƯỜNG TRUNG CẤP MIỀN TÂY', $course, $schedule, []);
echo "M01 regression passed; rendered sample with " . count($schedule) . " events.\n";
