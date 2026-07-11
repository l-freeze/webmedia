<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\List;

use App\Front\Application\Tag\List\Query\ITagQuery;

final readonly class Interactor
{
    public function __construct(private ITagQuery $tags)
    {
    }

    public function execute(Input $input): Output
    {
        $items = $this->tags->all();

        return new Output(items: $items);
    }
}
