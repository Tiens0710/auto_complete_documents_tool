<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use AIVANBAN\Support\Vietnamese;

final class FormCatalog
{
    private const COMMON = [
        1 => ['Tiến độ đào tạo', ['ThongTinKhoaHoc', 'LopHoc', 'LichDaoTao']],
        2 => ['Kế hoạch giáo viên', ['GiaoVien', 'PhanCongGiangDay']],
        3 => ['Sổ lên lớp', ['LopHoc', 'SinhVien', 'GiaoVien', 'LichDaoTao', 'Diem', 'ChuyenCan']],
        4 => ['Sổ tay giáo viên', ['LopHoc', 'SinhVien', 'GiaoVien', 'Diem', 'ChuyenCan']],
        5 => ['Sổ giáo án lý thuyết', ['GiaoVien', 'MonHocMoDun']],
        6 => ['Sổ giáo án thực hành', ['GiaoVien', 'MonHocMoDun']],
        7 => ['Sổ giáo án tích hợp', ['GiaoVien', 'MonHocMoDun']],
    ];

    private const BY_LEVEL = [
        'so_cap' => [
            8 => ['Kế hoạch đào tạo sơ cấp', ['ThongTinKhoaHoc', 'MonHocMoDun', 'LichDaoTao']],
            9 => ['Sổ cấp chứng chỉ sơ cấp', ['SinhVien', 'TotNghiepCapBang']],
            10 => ['Sổ cấp bản sao chứng chỉ sơ cấp', ['SinhVien', 'TotNghiepCapBang']],
            11 => ['Sổ quản lý học sinh sơ cấp', ['SinhVien', 'Diem', 'TotNghiepCapBang']],
        ],
        'trung_cap' => [
            12 => ['Kế hoạch đào tạo trung cấp', ['ThongTinKhoaHoc', 'MonHocMoDun', 'LichDaoTao']],
            13 => ['Sổ cấp bằng tốt nghiệp trung cấp', ['SinhVien', 'TotNghiepCapBang']],
            15 => ['Sổ quản lý học sinh trung cấp', ['SinhVien', 'Diem', 'TotNghiepCapBang']],
            17 => ['Sổ cấp bản sao bằng trung cấp', ['SinhVien', 'TotNghiepCapBang']],
        ],
        'cao_dang' => [
            12 => ['Kế hoạch đào tạo cao đẳng', ['ThongTinKhoaHoc', 'MonHocMoDun', 'LichDaoTao']],
            14 => ['Sổ cấp bằng tốt nghiệp cao đẳng', ['SinhVien', 'TotNghiepCapBang']],
            16 => ['Sổ quản lý sinh viên cao đẳng', ['SinhVien', 'Diem', 'TotNghiepCapBang']],
            18 => ['Sổ cấp bản sao bằng cao đẳng', ['SinhVien', 'TotNghiepCapBang']],
        ],
    ];

    public static function levels(): array
    {
        return [
            'so_cap' => 'Sơ cấp',
            'trung_cap' => 'Trung cấp',
            'cao_dang' => 'Cao đẳng',
        ];
    }

    public static function package(string $level, array $availableSheets = []): array
    {
        if (!isset(self::BY_LEVEL[$level])) {
            throw new \InvalidArgumentException('Trình độ đào tạo không hợp lệ.');
        }

        $forms = self::COMMON + self::BY_LEVEL[$level];
        $normalizedSheets = array_map([self::class, 'normalize'], $availableSheets);

        $result = [];
        foreach ($forms as $number => [$name, $requiredSheets]) {
            $missing = array_values(array_filter(
                $requiredSheets,
                static fn (string $sheet): bool => !in_array(self::normalize($sheet), $normalizedSheets, true)
            ));
            $result[] = [
                'number' => $number,
                'name' => $name,
                'required_sheets' => $requiredSheets,
                'missing_sheets' => $missing,
                'ready' => $missing === [],
                'scope' => in_array($number, [4, 5, 6, 7], true) ? 'Giáo viên/môn học' : (in_array($number, [9, 10, 11, 13, 14, 15, 16, 17, 18], true) ? 'Người học/sổ tổng hợp' : 'Lớp/khóa'),
            ];
        }

        return $result;
    }

    private static function normalize(string $value): string
    {
        return Vietnamese::compactKey($value);
    }
}
