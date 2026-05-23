<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit;

use App\Cms\Application\Article\Edit\Query\IArticleQuery;
use App\Cms\Application\Article\Edit\Query\ICategoryQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles, private ICategoryQuery $categories)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($input->id === null ? null : $this->articles->find($input->id), $this->categories->categories());
    }
}
