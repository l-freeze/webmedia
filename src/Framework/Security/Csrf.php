<?php

declare(strict_types=1);

namespace App\Framework\Security;

final class Csrf
{
    private const TOKEN_RANDOM_BYTES = 32;

    public function __construct(private Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(key: '_csrf');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(self::TOKEN_RANDOM_BYTES));
            $this->session->set(key: '_csrf', value: $token);
        }

        return $token;
    }

    public function validate(string $token): bool
    {
        $storedToken = $this->session->get(key: '_csrf');
        return is_string($storedToken) && hash_equals($storedToken, $token);
    }
}
