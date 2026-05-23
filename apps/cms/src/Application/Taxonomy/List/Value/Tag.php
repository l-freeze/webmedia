<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\List\Value;

final readonly class Tag
{
    public function __construct(public string $name, public string $slug)
    {
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return [$this->name, $this->slug, ''];
    }
}
