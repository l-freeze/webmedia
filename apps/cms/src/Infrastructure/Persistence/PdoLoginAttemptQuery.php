<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Auth\Login\Query\ILoginAttemptQuery;
use App\Cms\Application\Auth\Login\Value\LoginAttemptFailureCounts;
use App\Cms\Domain\Value\LoginAttemptResult;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use DateTimeImmutable;
use PDO;

final readonly class PdoLoginAttemptQuery implements ILoginAttemptQuery
{
    public function __construct(private PDO $pdo, private LoginAttemptEmailHash $emailHash)
    {
    }

    public function countFailures(string $email, string $ipAddress, DateTimeImmutable $since): LoginAttemptFailureCounts
    {
        return new LoginAttemptFailureCounts(
            byEmailAndIp: $this->countFailuresByEmailAndIp(email: $email, ipAddress: $ipAddress, since: $since),
            byIp: $this->countFailuresByIp(ipAddress: $ipAddress, since: $since),
        );
    }

    private function countFailuresByEmailAndIp(string $email, string $ipAddress, DateTimeImmutable $since): int
    {
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: 'SELECT COUNT(*) FROM login_attempts WHERE email_hash = :email_hash AND ip_address = :ip_address AND result = :result AND attempted_at >= :since',
        );
        PdoHelper::execute(statement: $stmt, params: [
            'email_hash' => $this->emailHash->hash(email: $email),
            'ip_address' => $ipAddress,
            'result' => LoginAttemptResult::Failure->value,
            'since' => $since->format('Y-m-d H:i:s'),
        ]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count');
    }

    private function countFailuresByIp(string $ipAddress, DateTimeImmutable $since): int
    {
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: 'SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip_address AND result = :result AND attempted_at >= :since',
        );
        PdoHelper::execute(statement: $stmt, params: [
            'ip_address' => $ipAddress,
            'result' => LoginAttemptResult::Failure->value,
            'since' => $since->format('Y-m-d H:i:s'),
        ]);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count');
    }
}
