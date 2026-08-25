<?php

declare(strict_types=1);

namespace Tests\Cms\Infrastructure\Logging;

use App\Cms\Infrastructure\Logging\NonBlockingAuditLogger;
use App\Shared\Audit\Application\IAuditLogger;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Logging\Infrastructure\LogWriteException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class NonBlockingAuditLoggerTest extends TestCase
{
    public function testApplicationPolicyReportsPersistenceFailureAndContinues(): void
    {
        $logger = new class implements IAuditLogger {
            public function record(AuditEvent $event): void
            {
                throw new LogWriteException('disk is full');
            }
        };
        /** @var list<string> $messages */
        $messages = [];
        $nonBlockingLogger = new NonBlockingAuditLogger(
            logger: $logger,
            failureReporter: static function (string $message) use (&$messages): void {
                $messages[] = $message;
            },
        );

        $nonBlockingLogger->record(event: AuditEvent::success(
            action: 'article.updated',
            resourceType: 'article',
        ));

        self::assertCount(1, $messages);
        self::assertStringContainsString('action=article.updated', $messages[0]);
        self::assertStringContainsString('error=disk is full', $messages[0]);
    }

    public function testApplicationPolicyDoesNotHideUnexpectedLoggerFailure(): void
    {
        $logger = new class implements IAuditLogger {
            public function record(AuditEvent $event): void
            {
                throw new LogicException('invalid audit mapping');
            }
        };
        $nonBlockingLogger = new NonBlockingAuditLogger(
            logger: $logger,
            failureReporter: static function (string $message): void {
            },
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('invalid audit mapping');

        $nonBlockingLogger->record(event: AuditEvent::success(
            action: 'article.updated',
            resourceType: 'article',
        ));
    }
}
