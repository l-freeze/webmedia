<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\AuthorizeRoute;

final readonly class Output
{
    public function __construct(public bool $allowed)
    {
    }
}
