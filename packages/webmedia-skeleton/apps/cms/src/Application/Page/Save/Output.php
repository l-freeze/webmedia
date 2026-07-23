<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Domain\Value\PageId;
use App\Shared\Validation\ValidationOutput;

final readonly class Output
{
    public function __construct(public ?PageId $pageId, public ValidationOutput $validation)
    {
    }

    public static function saved(PageId $pageId): self
    {
        return new self(pageId: $pageId, validation: ValidationOutput::valid());
    }

    public static function invalid(ValidationOutput $validation): self
    {
        return new self(pageId: null, validation: $validation);
    }

    public function isSaved(): bool
    {
        return $this->pageId !== null;
    }
}
