<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

final readonly class LoginAttemptEmailHash
{
    public function __construct(private string $key)
    {
    }

    public function hash(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), $this->key);
    }
}
