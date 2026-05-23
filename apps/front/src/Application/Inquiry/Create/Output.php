<?php

declare(strict_types=1);

namespace App\Front\Application\Inquiry\Create;

final readonly class Output
{
    public function __construct(public bool $created)
    {
    }
}
