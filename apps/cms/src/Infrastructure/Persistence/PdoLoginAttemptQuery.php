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
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: 'SELECT COUNT(*) AS by_ip, SUM(CASE WHEN email_hash = :email_hash THEN 1 ELSE 0 END) AS by_email_and_ip FROM login_attempts WHERE ip_address = :ip_address AND result = :result AND attempted_at >= :since',
        );
        PdoHelper::execute(statement: $stmt, params: [
            'email_hash' => $this->emailHash->hash(email: $email),
            'ip_address' => $ipAddress,
            'result' => LoginAttemptResult::Failure->value,
            'since' => $since->format('Y-m-d H:i:s'),
        ]);
        $row = PdoHelper::fetch(statement: $stmt);
        $counts = PdoRow::from(values: $row ?? []);

        return new LoginAttemptFailureCounts(
            byEmailAndIp: $counts->int(key: 'by_email_and_ip'),
            byIp: $counts->int(key: 'by_ip'),
        );
    }
}
