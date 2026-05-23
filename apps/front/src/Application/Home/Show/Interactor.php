<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show;

use App\Front\Application\Home\Show\Query\IArticleQuery;
use App\Front\Application\Home\Show\Query\ICategoryQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles, private ICategoryQuery $categories)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->articles->latest(), $this->categories->all());
    }
}
