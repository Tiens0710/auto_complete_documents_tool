<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use PDO;
use RuntimeException;

final class CredentialApp
{
    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function config(): array
    {
        $path = self::root() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'chungchi-config.php';
        if (!is_file($path)) {
            throw new RuntimeException('Chưa cấu hình kết nối cơ sở dữ liệu cho trang văn bằng.');
        }

        $config = require $path;
        if (!is_array($config) || !isset($config['database'], $config['public_base_url'])) {
            throw new RuntimeException('Cấu hình trang văn bằng chưa đầy đủ.');
        }

        foreach (['host', 'name', 'username', 'password'] as $key) {
            if (!isset($config['database'][$key])
                || str_starts_with((string) $config['database'][$key], 'CPANEL_')
                || str_starts_with((string) $config['database'][$key], 'CHANGE_THIS')) {
                throw new RuntimeException('Vui lòng cập nhật cấu hình MySQL trong storage/chungchi-config.php.');
            }
        }
        if (!isset($config['admin_password'])
            || strlen((string) $config['admin_password']) < 16
            || str_starts_with((string) $config['admin_password'], 'CHANGE_THIS')) {
            throw new RuntimeException('Mật khẩu quản trị cần được đặt thành chuỗi riêng tối thiểu 16 ký tự.');
        }

        $publicUrl = (string) $config['public_base_url'];
        $url = parse_url($publicUrl);
        if (!is_array($url)
            || strtolower((string) ($url['scheme'] ?? '')) !== 'https'
            || !isset($url['host'])
            || isset($url['user'])
            || isset($url['pass'])
            || isset($url['query'])
            || isset($url['fragment'])
            || !in_array((string) ($url['path'] ?? ''), ['', '/'], true)) {
            throw new RuntimeException('public_base_url cần là địa chỉ HTTPS của cổng tra cứu, ví dụ https://chungchi.mtpc.edu.vn.');
        }

        return $config;
    }

    public static function connect(array $config): PDO
    {
        $db = $config['database'];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['name']);

        return new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function pdfPath(array $record): ?string
    {
        $root = self::root();
        $pdf = realpath($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . (string) ($record['pdf_path'] ?? ''));
        $credentialDirectory = realpath($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'credentials');

        if ($credentialDirectory === false || $pdf === false || !is_file($pdf)) {
            return null;
        }

        $prefix = $credentialDirectory . DIRECTORY_SEPARATOR;
        if (!str_starts_with($pdf, $prefix)) {
            return null;
        }

        return $pdf;
    }
}
