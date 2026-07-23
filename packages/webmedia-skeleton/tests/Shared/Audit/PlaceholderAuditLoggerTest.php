<?php

declare(strict_types=1);

namespace Tests\Shared\Audit;

use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Audit\Infrastructure\PlaceholderAuditLogger;
use LogicException;
use PHPUnit\Framework\TestCase;

final class PlaceholderAuditLoggerTest extends TestCase
{
    public function testThrowsWhenAuditLoggerIsNotConfigured(): void
    {
        $logger = new PlaceholderAuditLogger();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logger is not configured.');

        $logger->record(event: AuditEvent::success(
            action: 'article.update',
            resourceType: 'article',
            resourceId: '1',
            actorUserId: '10',
        ));
    }
}
