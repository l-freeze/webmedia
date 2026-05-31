<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Upload;

final readonly class Output
{
    public function __construct(public bool $uploaded, public ?string $error = null)
    {
    }
}
