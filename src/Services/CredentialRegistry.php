<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use PDO;

final class CredentialRegistry
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    public static function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function createDraft(array $student, string $credentialType, string $token, string $pdfPath): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO credentials
                 (token_hash, student_code, full_name, date_of_birth, place_of_birth, credential_type, program,
                 graduation_classification, diploma_number, diploma_issue_date, register_number,
                 pdf_path, status, created_at)
             VALUES
                (:token_hash, :student_code, :full_name, :date_of_birth, :place_of_birth, :credential_type, :program,
                 :graduation_classification, :diploma_number, :diploma_issue_date, :register_number,
                 :pdf_path, \'draft\', CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'token_hash' => self::tokenHash($token),
            'student_code' => $student['student_code'],
            'full_name' => $student['full_name'],
            'date_of_birth' => $student['date_of_birth'],
            'place_of_birth' => $student['place_of_birth'],
            'credential_type' => $credentialType,
            'program' => $student['major'],
            'graduation_classification' => $student['graduation_classification'],
            'diploma_number' => $student['diploma_number'],
            'diploma_issue_date' => $student['diploma_issue_date'],
            'register_number' => $student['diploma_register_number'],
            'pdf_path' => $pdfPath,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function hasDiplomaNumber(string $credentialType, string $number): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM credentials WHERE credential_type = :credential_type AND diploma_number = :diploma_number LIMIT 1'
        );
        $statement->execute([
            'credential_type' => $credentialType,
            'diploma_number' => $number,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function findByToken(string $token): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, student_code, full_name, date_of_birth, place_of_birth, credential_type, program,
                    graduation_classification, diploma_number, diploma_issue_date, register_number,
                    pdf_path, status, created_at
             FROM credentials WHERE token_hash = :token_hash LIMIT 1'
        );
        $statement->execute(['token_hash' => self::tokenHash($token)]);
        $record = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($record) ? $record : null;
    }

    public function recent(int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        $statement = $this->pdo->query(
            'SELECT id, student_code, full_name, credential_type, program, diploma_number, diploma_issue_date,
                    status, created_at
             FROM credentials ORDER BY id DESC LIMIT ' . $limit
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
