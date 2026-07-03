<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Domain\Repository\ILoginAttemptRepository;
use App\Shared\Database\PdoHelper;
use PDO;

final readonly class PdoLoginAttemptRepository implements ILoginAttemptRepository
{
    public function __construct(private PDO $pdo, private LoginAttemptEmailHash $emailHash)
    {
    }

    public function save(LoginAttempt $attempt): void
    {
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: 'INSERT INTO login_attempts (email_hash, ip_address, result, attempted_at) VALUES (:email_hash, :ip_address, :result, :attempted_at)',
        );
        PdoHelper::execute(statement: $stmt, params: [
            'email_hash' => $this->emailHash->hash(email: $attempt->email),
            'ip_address' => $attempt->ipAddress,
            'result' => $attempt->result->value,
            'attempted_at' => $attempt->attemptedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
