<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\List;

use App\Cms\Application\Taxonomy\List\Query\ICategoryQuery;
use App\Cms\Application\Taxonomy\List\Query\ITagQuery;

final readonly class Interactor
{
    public function __construct(private ICategoryQuery $categories, private ITagQuery $tags)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(rows: array_merge($this->categories->categories(), $this->tags->tags()));
    }
}
