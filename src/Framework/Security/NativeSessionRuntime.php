<?php

declare(strict_types=1);

namespace Pera\Framework\Security;

final class NativeSessionRuntime
{
    private const EXPIRED_COOKIE_OFFSET_SECONDS = 42000;

    public function __construct(
        private readonly string $cookieSameSite = 'Lax',
        private readonly bool $forceSecureCookie = false,
    ) {
        if (!in_array($this->cookieSameSite, ['Lax', 'Strict', 'None'], true)) {
            throw new \InvalidArgumentException('Session cookie SameSite must be Lax, Strict, or None.');
        }
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
        session_start();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function close(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_write_close();
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

        $params = session_get_cookie_params();
        $name = session_name();
        if ($name !== false) {
            setcookie($name, '', [
                'expires' => time() - self::EXPIRED_COOKIE_OFFSET_SECONDS,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }

        session_destroy();
    }

    private function secureCookie(bool $secure): bool
    {
        return $secure || $this->forceSecureCookie || $this->cookieSameSite === 'None';
    }
}
