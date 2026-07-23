<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Auth\Login\Query\ILoginAttemptQuery;
use App\Cms\Application\Auth\Login\Value\LoginAttemptFailureCounts;
use App\Cms\Domain\Value\CmsLoginAttemptResetScope;
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
        $ipSince = $this->laterDateTime($since, $this->latestResetAtForIp($ipAddress));
        $emailAndIpSince = $this->laterDateTime($ipSince, $this->latestResetAtForEmailAndIp($email, $ipAddress));

        return new LoginAttemptFailureCounts(
            byEmailAndIp: $this->countFailuresByEmailAndIp($email, $ipAddress, $emailAndIpSince),
            byIp: $this->countFailuresByIp($ipAddress, $ipSince),
        );
    }

    private function countFailuresByEmailAndIp(string $email, string $ipAddress, DateTimeImmutable $since): int
    {
        $sql = <<<'SQL'
SELECT COUNT(*) AS count
FROM cms_login_attempts
WHERE ip_address = :ip_address
  AND email_hash = :email_hash
  AND result = :result
  AND attempted_at >= :since
SQL;

        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: $sql,
        );
        PdoHelper::execute(statement: $stmt, params: [
            'email_hash' => $this->emailHash->hash(email: $email),
            'ip_address' => $ipAddress,
            'result' => LoginAttemptResult::Failure->value,
            'since' => $since->format('Y-m-d H:i:s'),
        ]);
        $row = PdoHelper::fetch(statement: $stmt);
        $counts = PdoRow::from(values: $row ?? []);

        return $counts->int(key: 'count');
    }

    private function countFailuresByIp(string $ipAddress, DateTimeImmutable $since): int
    {
        $sql = <<<'SQL'
SELECT COUNT(*) AS count
FROM cms_login_attempts
WHERE ip_address = :ip_address
  AND result = :result
  AND attempted_at >= :since
SQL;

        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: $sql,
        );
        PdoHelper::execute(statement: $stmt, params: [
            'ip_address' => $ipAddress,
            'result' => LoginAttemptResult::Failure->value,
            'since' => $since->format('Y-m-d H:i:s'),
        ]);
        $row = PdoHelper::fetch(statement: $stmt);
        $counts = PdoRow::from(values: $row ?? []);

        return $counts->int(key: 'count');
    }

    private function latestResetAtForEmailAndIp(string $email, string $ipAddress): ?DateTimeImmutable
    {
        $sql = <<<'SQL'
SELECT MAX(reset_at) AS reset_at
FROM cms_login_attempt_resets
WHERE scope = :scope
  AND email_hash = :email_hash
  AND ip_address = :ip_address
SQL;

        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: $sql,
        );
        PdoHelper::execute(statement: $stmt, params: [
            'scope' => CmsLoginAttemptResetScope::EmailIp->value,
            'email_hash' => $this->emailHash->hash(email: $email),
            'ip_address' => $ipAddress,
        ]);

        return $this->dateTimeFromMaxReset(PdoHelper::fetch(statement: $stmt));
    }

    private function latestResetAtForIp(string $ipAddress): ?DateTimeImmutable
    {
        $sql = <<<'SQL'
SELECT MAX(reset_at) AS reset_at
FROM cms_login_attempt_resets
WHERE scope = :scope
  AND ip_address = :ip_address
SQL;

        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: $sql,
        );
        PdoHelper::execute(statement: $stmt, params: [
            'scope' => CmsLoginAttemptResetScope::Ip->value,
            'ip_address' => $ipAddress,
        ]);

        return $this->dateTimeFromMaxReset(PdoHelper::fetch(statement: $stmt));
    }

    private function laterDateTime(DateTimeImmutable $left, ?DateTimeImmutable $right): DateTimeImmutable
    {
        if ($right === null) {
            return $left;
        }

        return $right > $left ? $right : $left;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function dateTimeFromMaxReset(?array $row): ?DateTimeImmutable
    {
        $value = PdoRow::from(values: $row ?? [])->nullableString(key: 'reset_at');
        if ($value === null || $value === '') {
            return null;
        }

        return new DateTimeImmutable($value);
    }
}
