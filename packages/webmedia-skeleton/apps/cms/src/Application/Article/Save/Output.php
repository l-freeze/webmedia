<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Domain\Value\ArticleId;
use App\Shared\Validation\ValidationOutput;

final readonly class Output
{
    public function __construct(public ?ArticleId $articleId, public ValidationOutput $validation)
    {
    }

    public static function saved(ArticleId $articleId): self
    {
        return new self(articleId: $articleId, validation: ValidationOutput::valid());
    }

    public static function invalid(ValidationOutput $validation): self
    {
        return new self(articleId: null, validation: $validation);
    }

    public function isSaved(): bool
    {
        return $this->articleId !== null;
    }
}
