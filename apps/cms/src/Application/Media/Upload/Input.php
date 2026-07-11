<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Upload;

final readonly class Input
{
    /** @param array<string, mixed> $file */
    private function __construct(public array $file, public string $altText, public string $caption)
    {
    }

    /** @param array<string, mixed> $file */
    public static function create(array $file, string $altText = '', string $caption = ''): self
    {
        return new self(file: $file, altText: trim($altText), caption: trim($caption));
    }
}
