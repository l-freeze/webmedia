<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\List\Value;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $description,
    ) {
    }

    /** @return array{id:int,type:string,name:string,slug:string,description:string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'category',
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
