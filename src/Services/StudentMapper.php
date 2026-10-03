<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Vietnamese;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class StudentMapper
{
    private const FIELDS = [
        'student_code' => ['label' => 'Mã học sinh/sinh viên', 'required' => true, 'aliases' => ['tt', 'stt', 'so tt', 'mssv', 'ma sv', 'ma sinh vien', 'ma hoc sinh', 'student id']],
        'full_name' => ['label' => 'Họ và tên', 'required' => true, 'aliases' => ['ho ten', 'ho va ten', 'ten sinh vien', 'ten hoc sinh', 'full name']],
        'middle_name' => ['label' => 'Họ đệm', 'required' => false, 'aliases' => ['ho dem']],
        'given_name' => ['label' => 'Tên', 'required' => false, 'aliases' => ['ten']],
        'date_of_birth' => ['label' => 'Ngày sinh', 'required' => true, 'aliases' => ['ngay sinh', 'ngaysinh', 'date of birth', 'dob']],
        'gender' => ['label' => 'Giới tính', 'required' => false, 'aliases' => ['gioi tinh', 'phai', 'gender']],
        'place_of_birth' => ['label' => 'Nơi sinh', 'required' => false, 'aliases' => ['noi sinh', 'noisinh']],
        'citizen_id' => ['label' => 'Số CCCD', 'required' => false, 'aliases' => ['so cccd', 'cccd', 'so can cuoc cong dan']],
        'citizen_id_issue_date' => ['label' => 'Ngày cấp CCCD', 'required' => false, 'aliases' => ['ngay cap', 'ngay cap cccd', 'ngay cap cmnd']],
        'hometown' => ['label' => 'Quê quán', 'required' => false, 'aliases' => ['que quan', 'nguyen quan']],
        'permanent_address' => ['label' => 'Địa chỉ thường trú', 'required' => false, 'aliases' => ['thuong tru', 'dia chi thuong tru', 'ho khau']],
        'contact_address' => ['label' => 'Địa chỉ liên hệ', 'required' => false, 'aliases' => ['dia chi lien he', 'dia chi lien lac']],
        'phone' => ['label' => 'Điện thoại', 'required' => false, 'aliases' => ['dien thoai', 'so dien thoai', 'sdt', 'phone']],
        'ethnicity' => ['label' => 'Dân tộc', 'required' => false, 'aliases' => ['dan toc']],
        'religion' => ['label' => 'Tôn giáo', 'required' => false, 'aliases' => ['ton giao']],
        'nationality' => ['label' => 'Quốc tịch', 'required' => false, 'aliases' => ['quoc tich']],
        'admission_date' => ['label' => 'Ngày nhập học', 'required' => false, 'aliases' => ['ngay nhap hoc']],
        'graduation_year' => ['label' => 'Ngày tốt nghiệp', 'required' => false, 'aliases' => ['ngay tot nghiep']],
        'graduation_classification' => ['label' => 'Xếp loại tốt nghiệp', 'required' => false, 'aliases' => ['xep loai']],
        'diploma_number' => ['label' => 'Số hiệu văn bằng', 'required' => false, 'aliases' => ['so hieu van bang']],
        'diploma_issue_date' => ['label' => 'Ngày cấp văn bằng', 'required' => false, 'aliases' => ['ngay cap van bang']],
        'diploma_register_number' => ['label' => 'Số vào sổ cấp bằng', 'required' => false, 'aliases' => ['so vao so cap bang']],
        'theory_exam_score' => ['label' => 'Điểm thi lý thuyết', 'required' => false, 'aliases' => ['diem thi ly thuyet']],
        'practical_exam_score' => ['label' => 'Điểm thi thực hành', 'required' => false, 'aliases' => ['diem thi thuc hanh']],
        'course_final_score' => ['label' => 'ĐTKKH', 'required' => false, 'aliases' => ['dtkkh']],
        'class_code' => ['label' => 'Lớp', 'required' => false, 'aliases' => ['lop', 'ma lop', 'lop hoc']],
        'course' => ['label' => 'Khóa', 'required' => false, 'aliases' => ['khoa', 'khoa hoc', 'nien khoa']],
        'major' => ['label' => 'Nghề/ngành đào tạo', 'required' => false, 'aliases' => ['nghe', 'nganh', 'nganh hoc', 'nghe dao tao']],
    ];

    public static function fields(): array
    {
        return self::FIELDS;
    }

    public static function suggest(array $headers): array
    {
        $mapping = [];
        foreach (self::FIELDS as $key => $definition) {
            $mapping[$key] = null;
            foreach ($headers as $index => $header) {
                $normalized = self::normalize((string) $header);
                $aliases = array_map([self::class, 'normalize'], array_merge([$definition['label']], $definition['aliases']));
                if (in_array($normalized, $aliases, true)) {
                    $mapping[$key] = $index;
                    break;
                }
            }
        }
        return $mapping;
    }

    public static function mapRows(array $rows, array $headers, array $mapping): array
    {
        $students = [];
        $errors = [];
        $seenCodes = [];

        foreach ($rows as $offset => $row) {
            if (count(array_filter($row, static fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $student = [];
            foreach (self::FIELDS as $key => $definition) {
                $column = $mapping[$key] ?? null;
                $student[$key] = $column === null ? '' : trim((string) ($row[$column] ?? ''));
                if (in_array($key, ['date_of_birth', 'citizen_id_issue_date', 'admission_date', 'diploma_issue_date'], true)) {
                    $student[$key] = self::formatDate($student[$key]);
                }
            }

            if ($student['full_name'] === '') {
                $student['full_name'] = trim(implode(' ', array_filter([
                    $student['middle_name'],
                    $student['given_name'],
                ], static fn (string $part): bool => $part !== '')));
            }

            foreach (self::FIELDS as $key => $definition) {
                if ($definition['required'] && $student[$key] === '') {
                    $errors[] = 'Dòng ' . ($offset + 2) . ': thiếu ' . $definition['label'] . '.';
                }
            }

            if ($student['student_code'] !== '') {
                if (isset($seenCodes[$student['student_code']])) {
                    $errors[] = 'Dòng ' . ($offset + 2) . ': trùng mã ' . $student['student_code'] . '.';
                }
                $seenCodes[$student['student_code']] = true;
            }

            $students[] = $student;
        }

        return ['students' => $students, 'errors' => $errors];
    }

    private static function normalize(string $value): string
    {
        return Vietnamese::key($value);
    }

    private static function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial >= 1 && $serial <= 2958465) {
                return ExcelDate::excelToDateTimeObject($serial)->format('d/m/Y');
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
}
