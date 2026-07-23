<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Delete;

use App\Cms\Domain\Repository\ITagRepository;
use App\Cms\Domain\Value\TagId;

final readonly class Interactor
{
    public function __construct(private ITagRepository $tags)
    {
    }

    public function execute(Input $input): Output
    {
        $tagId = new TagId(value: $input->id);
        $deleted = $this->tags->delete($tagId);
        if (!$deleted) {
            return new Output(deleted: false, error: '対象が見つからないか、すでに削除されています。');
        }

        return new Output(deleted: true);
    }
}
