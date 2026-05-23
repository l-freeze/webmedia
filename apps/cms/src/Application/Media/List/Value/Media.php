<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\List\Value;

final readonly class Media
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public int $fileSize,
        public string $createdAt,
    ) {
    }

    /** @return array<int, string|int> */
    public function toArray(): array
    {
        return [$this->fileName, $this->mimeType, $this->fileSize, $this->createdAt];
    }
}
