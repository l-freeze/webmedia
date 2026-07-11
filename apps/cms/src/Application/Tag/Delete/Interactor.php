<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Delete;

use App\Cms\Domain\Repository\ITagRepository;

final readonly class Interactor
{
    public function __construct(private ITagRepository $tags)
    {
    }

    public function execute(Input $input): Output
    {
        $deleted = $this->tags->delete($input->id);
        if (!$deleted) {
            return new Output(deleted: false, error: '対象が見つからないか、すでに削除されています。');
        }

        return new Output(deleted: true);
    }
}
