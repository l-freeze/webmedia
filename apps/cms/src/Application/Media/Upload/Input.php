<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Upload;

final readonly class Input
{
    /** @param array<string, mixed> $file */
    public function __construct(public array $file, public string $altText = '', public string $caption = '')
    {
    }
}
