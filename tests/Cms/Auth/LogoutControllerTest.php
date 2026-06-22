<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Presentation\Http\Controller\Auth\Logout\Controller;
use App\Framework\Http\Request;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use App\Shared\SecurityLog\Application\SecurityLoggerInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use PHPUnit\Framework\TestCase;

final class LogoutControllerTest extends TestCase
{
    public function testLogoutRecordsSecurityLogBeforeDestroyingSession(): void
    {
        $sessionData = [
            'cms.user_id' => 42,
            'cms.role' => 'editor',
        ];
        $session = new Session(state: new SessionState(data: $sessionData), runtime: new NativeSessionRuntime());
        $securityLogger = new RecordingLogoutSecurityLogger();
        $controller = new Controller(session: $session, securityLogger: $securityLogger);

        $response = $controller(new Request(
            method: 'POST',
            path: '/admin/logout',
            query: [],
            body: [],
            server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit Browser',
                'HTTP_X_REQUEST_ID' => 'request-123',
            ],
        ));

        self::assertSame(302, $response->status);
        self::assertSame('/admin/login', $response->headers['Location'] ?? null);
        self::assertFalse($session->has(key: 'cms.user_id'));
        self::assertCount(1, $securityLogger->events);

        $event = $securityLogger->events[0];
        self::assertSame('cms.logout', $event->eventType);
        self::assertSame('success', $event->result);
        self::assertSame('42', $event->actorUserId);
        self::assertSame('editor', $event->actorRole);
        self::assertSame('127.0.0.1', $event->ipAddress);
        self::assertSame('PHPUnit Browser', $event->userAgent);
        self::assertSame('request-123', $event->requestId);
    }
}

final class RecordingLogoutSecurityLogger implements SecurityLoggerInterface
{
    /** @var list<SecurityLogEvent> */
    public array $events = [];

    public function record(SecurityLogEvent $event): void
    {
        $this->events[] = $event;
    }
}
