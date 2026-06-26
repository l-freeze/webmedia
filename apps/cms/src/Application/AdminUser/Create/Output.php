<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Create;

final readonly class Output
{
    public function __construct(public bool $created, public ?int $userId = null, public ?string $error = null)
    {
    }
}
