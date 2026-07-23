<?php

declare(strict_types=1);

namespace App\Front\Application\Category\Show\Value;

final readonly class CategoryDetail
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
    ) {
    }

    /** @return array{name:string,slug:string,description:?string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
