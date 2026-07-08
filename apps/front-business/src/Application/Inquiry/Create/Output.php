<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Inquiry\Create;

final readonly class Output
{
    public function __construct(public bool $created, public ?string $error = null)
    {
    }
}
