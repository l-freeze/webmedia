<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Domain\Entity\LoginAttempt;
use App\Cms\Infrastructure\Persistence\LoginAttemptEmailHash;
use App\Cms\Infrastructure\Persistence\PdoLoginAttemptQuery;
use App\Cms\Infrastructure\Persistence\PdoLoginAttemptRepository;
use DateTimeImmutable;
use PDO;
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
        $pdo->exec('CREATE TABLE login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, email_hash TEXT NOT NULL, ip_address TEXT NOT NULL, result TEXT NOT NULL, attempted_at TEXT NOT NULL)');

        $hash = new LoginAttemptEmailHash(key: 'test-key');
        $repository = new PdoLoginAttemptRepository(pdo: $pdo, emailHash: $hash);
        $query = new PdoLoginAttemptQuery(pdo: $pdo, emailHash: $hash);

        $repository->save(attempt: LoginAttempt::failure(
            email: 'Admin@Example.Test',
            ipAddress: '192.0.2.10',
            attemptedAt: new DateTimeImmutable('2026-07-03 10:00:00'),
        ));
        $repository->save(attempt: LoginAttempt::success(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            attemptedAt: new DateTimeImmutable('2026-07-03 10:01:00'),
        ));
        $repository->save(attempt: LoginAttempt::blocked(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            attemptedAt: new DateTimeImmutable('2026-07-03 10:02:00'),
        ));
        $repository->save(attempt: LoginAttempt::failure(
            email: 'other@example.test',
            ipAddress: '192.0.2.10',
            attemptedAt: new DateTimeImmutable('2026-07-03 10:03:00'),
        ));
        $repository->save(attempt: LoginAttempt::failure(
            email: 'admin@example.test',
            ipAddress: '198.51.100.20',
            attemptedAt: new DateTimeImmutable('2026-07-03 10:04:00'),
        ));
        $repository->save(attempt: LoginAttempt::failure(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            attemptedAt: new DateTimeImmutable('2026-07-03 09:00:00'),
        ));

        $counts = $query->countFailures(
            email: 'admin@example.test',
            ipAddress: '192.0.2.10',
            since: new DateTimeImmutable('2026-07-03 09:30:00'),
        );

        self::assertSame(1, $counts->byEmailAndIp);
        self::assertSame(2, $counts->byIp);
        $rawEmailStatement = $pdo->query("SELECT COUNT(*) FROM login_attempts WHERE email_hash = 'admin@example.test'");
        self::assertInstanceOf(\PDOStatement::class, $rawEmailStatement);
        self::assertSame(0, (int) $rawEmailStatement->fetchColumn());

        $firstHashStatement = $pdo->query('SELECT email_hash FROM login_attempts ORDER BY id LIMIT 1');
        self::assertInstanceOf(\PDOStatement::class, $firstHashStatement);
        self::assertSame($hash->hash(email: 'admin@example.test'), (string) $firstHashStatement->fetchColumn());
    }
}
