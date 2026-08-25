<?php

declare(strict_types=1);

namespace Pera\Framework\Security;

use Closure;
use RuntimeException;

final class NativeSessionRuntime
{
    private const EXPIRED_COOKIE_OFFSET_SECONDS = 42000;

    /** @var Closure(): bool */
    private readonly Closure $startOperation;

    /** @var Closure(bool): bool */
    private readonly Closure $regenerateOperation;

    /** @var Closure(): bool */
    private readonly Closure $closeOperation;

    /** @var Closure(): bool */
    private readonly Closure $destroyOperation;

    public function __construct(
        private readonly string $cookieSameSite = 'Lax',
        private readonly bool $forceSecureCookie = false,
        ?Closure $startOperation = null,
        ?Closure $regenerateOperation = null,
        ?Closure $closeOperation = null,
        ?Closure $destroyOperation = null,
    ) {
        if (!in_array($this->cookieSameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new \InvalidArgumentException('Session cookie SameSite must be Lax, Strict, or None.');
        }

        $this->startOperation = $startOperation ?? static fn (): bool => session_start();
        $this->regenerateOperation = $regenerateOperation
            ?? static fn (bool $deleteOldSession): bool => session_regenerate_id($deleteOldSession);
        $this->closeOperation = $closeOperation ?? static fn (): bool => session_write_close();
        $this->destroyOperation = $destroyOperation ?? static fn (): bool => session_destroy();
    }

    public function start(bool $secure): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secureCookie = $this->secureCookie(secure: $secure);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
        ini_set('session.cookie_samesite', $this->cookieSameSite);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => $this->cookieSameSite,
        ]);
        if (!(($this->startOperation)())) {
            throw new RuntimeException('Failed to start the native PHP session.');
        }
    }

    public function regenerate(): void
    {
        if (!(($this->regenerateOperation)(true))) {
            throw new RuntimeException('Failed to regenerate the native PHP session ID.');
        }
    }

    public function close(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (!(($this->closeOperation)())) {
            throw new RuntimeException('Failed to write and close the native PHP session.');
        }
    }

    public function cookieHeader(): ?string
    {
        $name = session_name();
        if ($name === false) {
            return null;
        }

        $id = session_id();
        if ($id === false || $id === '') {
            return null;
        }

        $params = session_get_cookie_params();
        $parts = [$name . '=' . rawurlencode($id)];
        $parts[] = 'Path=' . $params['path'];
        if ($params['domain'] !== '') {
            $parts[] = 'Domain=' . $params['domain'];
        }
        if ($params['secure']) {
            $parts[] = 'Secure';
        }
        if ($params['httponly']) {
            $parts[] = 'HttpOnly';
        }
        $parts[] = 'SameSite=' . $params['samesite'];

        return implode('; ', $parts);
    }

    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (!(($this->destroyOperation)())) {
            throw new RuntimeException('Failed to destroy the native PHP session.');
        }

        $params = session_get_cookie_params();
        $name = session_name();
        if ($name === false) {
            return;
        }

        setcookie($name, '', [
            'expires' => time() - self::EXPIRED_COOKIE_OFFSET_SECONDS,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }

    private function secureCookie(bool $secure): bool
    {
        return $secure || $this->forceSecureCookie || $this->cookieSameSite === 'None';
    }
}
