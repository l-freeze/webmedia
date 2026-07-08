<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Home\Show\Value;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
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
            'description' => $this->description,
            'article_count' => $this->articleCount,
        ];
    }
}
