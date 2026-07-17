<?php

declare(strict_types=1);

namespace Tests\Framework;

use Pera\Framework\Http\Middleware\CallableRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\CsrfMiddleware;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SESSION = [];
    }

    public function testExistingInMemorySessionApiRemainsAvailable(): void
    {
        $data = [];
        $session = new Session(
            state: new SessionState(data: $data),
            runtime: new NativeSessionRuntime(),
        );

        $session->set(key: 'user_id', value: 42);
        $session->flash(key: 'success', value: 'Saved');

        self::assertTrue($session->has(key: 'user_id'));
        self::assertSame(42, $session->get(key: 'user_id'));
        self::assertSame('Saved', $session->pullFlash(key: 'success'));
        self::assertNull($session->pullFlash(key: 'success'));

        $session->remove(key: 'user_id');

        self::assertFalse($session->has(key: 'user_id'));
        self::assertSame([], $data['_flash'] ?? null);
    }

    public function testNativeSessionStartsOnFirstStateAccess(): void
    {
        $runtime = new NativeSessionRuntime();
        $session = Session::fromNativeGlobals(runtime: $runtime, secure: false);

        self::assertSame(PHP_SESSION_NONE, session_status());

        $session->set(key: 'message', value: 'hello');
        $sessionId = session_id();

        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertNotSame('', $sessionId);
        self::assertSame('hello', $session->get(key: 'message'));
        self::assertSame($sessionId, session_id());
    }

    public function testSafeCsrfMiddlewareRequestDoesNotStartNativeSession(): void
    {
        $runtime = new NativeSessionRuntime();
        $session = Session::fromNativeGlobals(runtime: $runtime, secure: false);
        $middleware = new CsrfMiddleware(csrf: new Csrf(session: $session));

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/articles/example', query: [], body: [], server: []),
            handler: new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok')),
        );

        self::assertSame(Response::HTTP_OK, $response->status);
        self::assertSame('ok', $response->body);
        self::assertSame(PHP_SESSION_NONE, session_status());
    }

    public function testCsrfTokenStartsNativeSession(): void
    {
        $runtime = new NativeSessionRuntime();
        $session = Session::fromNativeGlobals(runtime: $runtime, secure: false);
        $csrf = new Csrf(session: $session);

        $token = $csrf->token();

        self::assertNotSame('', $token);
        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertTrue($csrf->validate(token: $token));
    }

    public function testNativeLifecycleOperationsActivateBeforeRegenerateAndDestroy(): void
    {
        $runtime = new NativeSessionRuntime();
        $session = Session::fromNativeGlobals(runtime: $runtime, secure: false);

        $session->regenerate();

        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertNotSame('', session_id());

        $session->set(key: 'user_id', value: 42);
        $session->destroy();

        self::assertSame(PHP_SESSION_NONE, session_status());
        self::assertSame([], $_SESSION);
    }
}
