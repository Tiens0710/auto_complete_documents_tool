<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class TemplateGenerator
{
    private const SHEETS = [
        'ThongTinKhoaHoc' => ['Cơ quan chủ quản', 'Cơ sở đào tạo', 'Mã khóa', 'Tên khóa', 'Trình độ', 'Nghề đào tạo', 'Mã nghề', 'Ngày bắt đầu', 'Ngày kết thúc'],
        'LopHoc' => ['Mã lớp', 'Tên lớp', 'Mã khóa', 'Giáo viên chủ nhiệm'],
        'SinhVien' => ['MSSV', 'Họ và tên', 'Ngày sinh', 'Giới tính', 'Nơi sinh', 'Quê quán', 'Địa chỉ thường trú', 'Điện thoại', 'Dân tộc', 'Tôn giáo', 'Lớp', 'Khóa', 'Nghề đào tạo'],
        'GiaoVien' => ['Mã giáo viên', 'Họ và tên', 'Khoa/bộ môn'],
        'MonHocMoDun' => ['Mã môn/mô-đun', 'Tên môn/mô-đun', 'Lý thuyết', 'Thực hành', 'Kiểm tra'],
        'PhanCongGiangDay' => ['Mã giáo viên', 'Mã lớp', 'Mã môn/mô-đun', 'Từ ngày', 'Đến ngày', 'Số giờ'],
        'LichDaoTao' => ['Mã lớp', 'Tuần', 'Từ ngày', 'Đến ngày', 'Nội dung', 'Loại hoạt động'],
        'Diem' => ['MSSV', 'Mã môn/mô-đun', 'Điểm định kỳ 1', 'Điểm định kỳ 2', 'Điểm kết thúc lần 1', 'Điểm kết thúc lần 2', 'Điểm tổng kết'],
        'ChuyenCan' => ['MSSV', 'Ngày', 'Số giờ nghỉ có phép', 'Số giờ nghỉ không phép'],
        'TotNghiepCapBang' => ['MSSV', 'Số quyết định', 'Ngày quyết định', 'Xếp loại tốt nghiệp', 'Số hiệu bằng/chứng chỉ', 'Ngày cấp', 'Ngày nhận'],
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
