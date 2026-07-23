<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\Show\Value;

final readonly class TagDetail
{
    public function __construct(public string $name, public string $slug)
    {
    }

    /** @return array{name:string,slug:string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
