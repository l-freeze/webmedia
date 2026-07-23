<?php

declare(strict_types=1);

namespace Tests\Shared\Event;

use App\Shared\Audit\Application\IAuditLogger;
use App\Shared\Audit\Application\RecordAuditEventHandler;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Event\Application\IEventHandler;
use App\Shared\Event\Infrastructure\SynchronousEventBus;
use App\Shared\SecurityLog\Application\RecordSecurityLogEventHandler;
use App\Shared\SecurityLog\Application\ISecurityLogger;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use PHPUnit\Framework\TestCase;

final class SynchronousEventBusTest extends TestCase
{
    public function testDispatchCallsMatchingHandlersOnly(): void
    {
        $handled = new RecordingEventHandler(handledClass: ExampleEvent::class);
        $ignored = new RecordingEventHandler(handledClass: OtherExampleEvent::class);
        $event = new ExampleEvent();

        $bus = new SynchronousEventBus(handlers: [$handled, $ignored]);
        $bus->dispatch(event: $event);

        self::assertSame([$event], $handled->events);
        self::assertSame([], $ignored->events);
    }

    public function testAuditEventHandlerRecordsAuditEvent(): void
    {
        $logger = new RecordingEventAuditLogger();
        $event = new AuditEvent(
            action: 'article.updated',
            resourceType: 'article',
            result: 'success',
        );

        $handler = new RecordAuditEventHandler(logger: $logger);
        self::assertTrue($handler->handles(event: $event));
        $handler->handle(event: $event);

        self::assertSame([$event], $logger->events);
    }

    public function testSecurityLogEventHandlerRecordsSecurityLogEvent(): void
    {
        $logger = new RecordingEventSecurityLogger();
        $event = new SecurityLogEvent(
            eventType: 'cms.login',
            result: 'success',
        );

        $handler = new RecordSecurityLogEventHandler(logger: $logger);
        self::assertTrue($handler->handles(event: $event));
        $handler->handle(event: $event);

        self::assertSame([$event], $logger->events);
    }
}

final class ExampleEvent
{
}

final class OtherExampleEvent
{
}

final class RecordingEventHandler implements IEventHandler
{
    /** @var list<object> */
    public array $events = [];

    /** @param class-string $handledClass */
    public function __construct(private readonly string $handledClass)
    {
    }

    public function handles(object $event): bool
    {
        return $event instanceof $this->handledClass;
    }

    public function handle(object $event): void
    {
        $this->events[] = $event;
    }
}

final class RecordingEventAuditLogger implements IAuditLogger
{
    /** @var list<AuditEvent> */
    public array $events = [];

    public function record(AuditEvent $event): void
    {
        $this->events[] = $event;
    }
}

final class RecordingEventSecurityLogger implements ISecurityLogger
{
    /** @var list<SecurityLogEvent> */
    public array $events = [];

    public function record(SecurityLogEvent $event): void
    {
        $this->events[] = $event;
    }
}
