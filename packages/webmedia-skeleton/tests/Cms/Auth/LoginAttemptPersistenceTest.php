<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Domain\Entity\CmsLoginAttemptReset;
use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\IpAddress;
use App\Cms\Domain\Value\LoginAttemptedAt;
use App\Cms\Domain\Value\LoginAttemptEmail;
use App\Cms\Domain\Value\LoginAttemptResetAt;
use App\Cms\Domain\Value\LoginAttemptResetReason;
use App\Cms\Infrastructure\Persistence\LoginAttemptEmailHash;
use App\Cms\Infrastructure\Persistence\PdoCmsLoginAttemptResetRepository;
use App\Cms\Infrastructure\Persistence\PdoLoginAttemptQuery;
use App\Cms\Infrastructure\Persistence\PdoLoginAttemptRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LoginAttemptPersistenceTest extends TestCase
{
    public function testSavesHashedEmailAndCountsOnlyRecentFailures(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is not available.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE cms_login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, email_hash TEXT NOT NULL, ip_address TEXT NOT NULL, result TEXT NOT NULL, attempted_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE cms_login_attempt_resets (id INTEGER PRIMARY KEY AUTOINCREMENT, scope TEXT NOT NULL, email_hash TEXT NULL, ip_address TEXT NOT NULL, reset_at TEXT NOT NULL, reset_by_user_id INTEGER NOT NULL, reason TEXT NOT NULL)');

        $hash = new LoginAttemptEmailHash(key: 'test-key');
        $repository = new PdoLoginAttemptRepository(pdo: $pdo, emailHash: $hash);
        $resetRepository = new PdoCmsLoginAttemptResetRepository(pdo: $pdo, emailHash: $hash);
        $query = new PdoLoginAttemptQuery(pdo: $pdo, emailHash: $hash);

        $repository->save(attempt: LoginAttempt::failure(
            email: new LoginAttemptEmail(value: 'Admin@Example.Test'),
            ipAddress: new IpAddress(value: '192.0.2.10'),
            attemptedAt: new LoginAttemptedAt(value: new DateTimeImmutable('2026-07-03 10:00:00')),
        ));
        $repository->save(attempt: LoginAttempt::success(
            email: new LoginAttemptEmail(value: 'admin@example.test'),
            ipAddress: new IpAddress(value: '192.0.2.10'),
            attemptedAt: new LoginAttemptedAt(value: new DateTimeImmutable('2026-07-03 10:01:00')),
        ));
        $repository->save(attempt: LoginAttempt::blocked(
            email: new LoginAttemptEmail(value: 'admin@example.test'),
            ipAddress: new IpAddress(value: '192.0.2.10'),
            attemptedAt: new LoginAttemptedAt(value: new DateTimeImmutable('2026-07-03 10:02:00')),
        ));
        $repository->save(attempt: LoginAttempt::failure(
            email: new LoginAttemptEmail(value: 'other@example.test'),
            ipAddress: new IpAddress(value: '192.0.2.10'),
            attemptedAt: new LoginAttemptedAt(value: new DateTimeImmutable('2026-07-03 10:03:00')),
        ));
        $repository->save(attempt: LoginAttempt::failure(
            email: new LoginAttemptEmail(value: 'admin@example.test'),
            ipAddress: new IpAddress(value: '198.51.100.20'),
            attemptedAt: new LoginAttemptedAt(value: new DateTimeImmutable('2026-07-03 10:04:00')),
        ));
        $repository->save(attempt: LoginAttempt::failure(
            email: new LoginAttemptEmail(value: 'admin@example.test'),
            ipAddress: new IpAddress(value: '192.0.2.10'),
            attemptedAt: new LoginAttemptedAt(value: new DateTimeImmutable('2026-07-03 09:00:00')),
        ));

        $counts = $query->countFailures(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            since: new DateTimeImmutable('2026-07-03 09:30:00'),
        );

        self::assertSame(1, $counts->byEmailAndIp);
        self::assertSame(2, $counts->byIp);

        $resetRepository->save(reset: CmsLoginAttemptReset::emailIp(
            email: new LoginAttemptEmail(value: 'admin@example.test'),
            ipAddress: new IpAddress(value: '192.0.2.10'),
            resetAt: new LoginAttemptResetAt(value: new DateTimeImmutable('2026-07-03 10:02:30')),
            resetByUserId: new AdminUserId(value: 1),
            reason: new LoginAttemptResetReason(value: 'unlock'),
        ));

        $countsAfterEmailIpReset = $query->countFailures(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            since: new DateTimeImmutable('2026-07-03 09:30:00'),
        );

        self::assertSame(0, $countsAfterEmailIpReset->byEmailAndIp);
        self::assertSame(2, $countsAfterEmailIpReset->byIp);

        $resetRepository->save(reset: CmsLoginAttemptReset::ip(
            ipAddress: new IpAddress(value: '192.0.2.10'),
            resetAt: new LoginAttemptResetAt(value: new DateTimeImmutable('2026-07-03 10:03:30')),
            resetByUserId: new AdminUserId(value: 1),
            reason: new LoginAttemptResetReason(value: 'unlock ip'),
        ));

        $countsAfterIpReset = $query->countFailures(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            since: new DateTimeImmutable('2026-07-03 09:30:00'),
        );

        self::assertSame(0, $countsAfterIpReset->byEmailAndIp);
        self::assertSame(0, $countsAfterIpReset->byIp);

        $rawEmailStatement = $pdo->query("SELECT COUNT(*) FROM cms_login_attempts WHERE email_hash = 'admin@example.test'");
        self::assertInstanceOf(PDOStatement::class, $rawEmailStatement);
        self::assertSame(0, (int) $rawEmailStatement->fetchColumn());

        $firstHashStatement = $pdo->query('SELECT email_hash FROM cms_login_attempts ORDER BY id LIMIT 1');
        self::assertInstanceOf(PDOStatement::class, $firstHashStatement);
        self::assertSame($hash->hash(email: 'admin@example.test'), (string) $firstHashStatement->fetchColumn());
    }
}
