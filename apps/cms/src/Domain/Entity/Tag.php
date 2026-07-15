<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\TagId;
use App\Cms\Domain\Value\TagName;
use App\Cms\Domain\Value\TagSlug;

final readonly class Tag
{
    public function __construct(
        public ?TagId $id,
        public TagName $name,
        public TagSlug $slug,
    ) {
    }
}
