<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Tag\List\Value;

final readonly class Tag
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $articleCount,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'article_count' => $this->articleCount,
        ];
    }
}
