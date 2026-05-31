<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit\Value;

final readonly class Media
{
    public function __construct(public int $id, public string $fileName, public string $path, public string $altText)
    {
    }

    /** @return array<string, string|int> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->fileName,
            'path' => $this->path,
            'alt_text' => $this->altText,
        ];
    }
}
