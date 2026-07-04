<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Policy\Value;

final readonly class ArticlePolicyArticle
{
    public function __construct(
        public int $id,
        public string $status,
        public ?int $authorId,
    ) {
    }
}
