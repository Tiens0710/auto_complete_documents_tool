<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class TemplateGenerator
{
    private const SHEETS = [
        'SinhVien' => [
            'TT', 'Họ đệm', 'Tên', 'Giới tính', 'Ngày sinh', 'Nơi sinh', 'Số CCCD', 'Ngày cấp',
            'Số điện thoại', 'Dân tộc', 'Tôn giáo', 'Quốc tịch', 'Ngày nhập học', 'Ngày tốt nghiệp',
            'Xếp loại', 'Số hiệu văn bằng', 'Ngày cấp văn bằng', 'Số vào sổ cấp bằng',
            'Địa chỉ liên hệ', 'Điểm thi lý thuyết', 'Điểm thi thực hành', 'ĐTKKH',
        ],
    ];

    public function output(): never
    {
        $workbook = new Spreadsheet();
        $workbook->removeSheetByIndex(0);

        foreach (self::SHEETS as $name => $headers) {
            $sheet = $workbook->createSheet();
            $sheet->setTitle($name);
            $sheet->fromArray($headers, null, 'A1');
            $lastColumn = $sheet->getHighestColumn();
            $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A1:' . $lastColumn . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF185C53');
            $sheet->freezePane('A2');
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
        }

        $workbook->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="AIVANBAN-mau-du-lieu.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($workbook))->save('php://output');
        exit;
    }
}
