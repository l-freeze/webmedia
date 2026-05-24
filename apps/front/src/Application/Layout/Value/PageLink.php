<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Value;

final readonly class PageLink
{
    public function __construct(public string $title, public string $slug)
    {
    }

    /** @return array{title: string, slug: string} */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
        ];
    }
}
