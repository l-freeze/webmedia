<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Framework;

use Pera\Framework\Security\NativeSessionRuntime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function testThrowsWhenNativeSessionCannotStart(): void
    {
        $runtime = new NativeSessionRuntime(startOperation: static fn (): bool => false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to start the native PHP session.');

        $runtime->start(secure: false);
    }

    public function testThrowsWhenNativeSessionIdCannotBeRegenerated(): void
    {
        $runtime = new NativeSessionRuntime(regenerateOperation: static fn (bool $deleteOldSession): bool => false);
        $runtime->start(secure: false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to regenerate the native PHP session ID.');

        $runtime->regenerate();
    }

    public function testThrowsWhenNativeSessionCannotBeClosed(): void
    {
        $runtime = new NativeSessionRuntime(closeOperation: static fn (): bool => false);
        $runtime->start(secure: false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write and close the native PHP session.');

        $runtime->close();
    }

    public function testThrowsWhenNativeSessionCannotBeDestroyed(): void
    {
        $runtime = new NativeSessionRuntime(destroyOperation: static fn (): bool => false);
        $runtime->start(secure: false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to destroy the native PHP session.');

        $runtime->destroy();
    }
}
