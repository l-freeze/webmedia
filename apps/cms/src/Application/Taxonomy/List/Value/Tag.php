<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\List\Value;

final readonly class Tag
{
    public function __construct(public int $id, public string $name, public string $slug)
    {
    }

    /** @return array{id:int,type:string,name:string,slug:string,description:string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'tag',
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => '',
        ];
    }
}
