<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';

use AIVANBAN\Services\PdfDossierExporter;
use AIVANBAN\Services\StudentMapper;
use PhpOffice\PhpSpreadsheet\IOFactory;

$root=dirname(__DIR__);
$sources=glob($root.'/outputs/*/AIVANBAN-test-trung-cap.xlsx');
if(!$sources)throw new RuntimeException('Missing synthetic sample workbook.');
$book=IOFactory::load($sources[0]);
$inspection=['filename'=>basename($sources[0]),'sheet_data'=>[]];
foreach($book->getWorksheetIterator()as$sheet){$rows=$sheet->toArray('',true,false);$headers=array_shift($rows);$inspection['sheet_data'][$sheet->getTitle()]=['headers'=>$headers,'rows'=>$rows];}
$inspection['headers']=$inspection['sheet_data']['SinhVien']['headers'];
$inspection['student_rows']=array_slice($inspection['sheet_data']['SinhVien']['rows'],0,in_array('--five',$argv,true)?5:1);
$mapping=StudentMapper::suggest($inspection['headers']);
$out=$root.'/output/pdf/doi-chieu-18-mau';
if(!is_dir($out))mkdir($out,0775,true);
$results=[];
foreach(['so_cap','trung_cap','cao_dang']as$level){
    $archive=(new PdfDossierExporter())->buildArchive($level,$inspection,$mapping);
    $target=$out.'/AIVANBAN-'.$level.'.zip';copy($archive,$target);
    $zip=new ZipArchive();$zip->open($archive);
    for($i=0;$i<$zip->numFiles;$i++){$name=$zip->getNameIndex($i);if(str_ends_with($name,'.pdf'))file_put_contents($out.'/'.$name,$zip->getFromIndex($i));}
    $zip->close();$results[$level]=$target;
}
echo json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
