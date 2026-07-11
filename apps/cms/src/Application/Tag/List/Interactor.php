<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\List;

use App\Cms\Application\Tag\List\Query\ITagQuery;

final readonly class Interactor
{
    public function __construct(private ITagQuery $tags)
    {
    }

    public function execute(Input $input): Output
    {
        $tags = $this->tags->tags();

        return new Output(tags: $tags);
    }
}
