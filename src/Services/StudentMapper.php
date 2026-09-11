<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Vietnamese;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class StudentMapper
{
    private const FIELDS = [
        'student_code' => ['label' => 'Mã học sinh/sinh viên', 'required' => true, 'aliases' => ['mssv', 'ma sv', 'ma sinh vien', 'ma hoc sinh', 'student id']],
        'full_name' => ['label' => 'Họ và tên', 'required' => true, 'aliases' => ['ho ten', 'ho va ten', 'ten sinh vien', 'ten hoc sinh', 'full name']],
        'date_of_birth' => ['label' => 'Ngày sinh', 'required' => true, 'aliases' => ['ngay sinh', 'ngaysinh', 'date of birth', 'dob']],
        'gender' => ['label' => 'Giới tính', 'required' => false, 'aliases' => ['gioi tinh', 'phai', 'gender']],
        'place_of_birth' => ['label' => 'Nơi sinh', 'required' => false, 'aliases' => ['noi sinh', 'noisinh']],
        'hometown' => ['label' => 'Quê quán', 'required' => false, 'aliases' => ['que quan', 'nguyen quan']],
        'permanent_address' => ['label' => 'Địa chỉ thường trú', 'required' => false, 'aliases' => ['thuong tru', 'dia chi thuong tru', 'ho khau']],
        'phone' => ['label' => 'Điện thoại', 'required' => false, 'aliases' => ['dien thoai', 'so dien thoai', 'sdt', 'phone']],
        'ethnicity' => ['label' => 'Dân tộc', 'required' => false, 'aliases' => ['dan toc']],
        'religion' => ['label' => 'Tôn giáo', 'required' => false, 'aliases' => ['ton giao']],
        'class_code' => ['label' => 'Lớp', 'required' => true, 'aliases' => ['lop', 'ma lop', 'lop hoc']],
        'course' => ['label' => 'Khóa', 'required' => true, 'aliases' => ['khoa', 'khoa hoc', 'nien khoa']],
        'major' => ['label' => 'Nghề/ngành đào tạo', 'required' => true, 'aliases' => ['nghe', 'nganh', 'nganh hoc', 'nghe dao tao']],
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
                if ($key === 'date_of_birth') {
                    $student[$key] = self::formatDate($student[$key]);
                }
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
