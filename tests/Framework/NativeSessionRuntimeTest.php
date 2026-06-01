<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Security\NativeSessionRuntime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NativeSessionRuntimeTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function testCookieHeaderUsesSecureHttpOnlyAndConfiguredSameSite(): void
    {
        $runtime = new NativeSessionRuntime(cookieSameSite: 'Strict', forceSecureCookie: true);

        $runtime->start(secure: false);
        $header = $runtime->cookieHeader();
        $runtime->close();

        self::assertIsString($header);
        self::assertStringContainsString('Secure', $header);
        self::assertStringContainsString('HttpOnly', $header);
        self::assertStringContainsString('SameSite=Strict', $header);
    }

    public function testRejectsInvalidSameSiteValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new NativeSessionRuntime(cookieSameSite: 'Invalid');
    }
}
