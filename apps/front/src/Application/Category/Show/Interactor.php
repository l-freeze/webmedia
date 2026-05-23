<?php

declare(strict_types=1);

namespace App\Front\Application\Category\Show;

use App\Front\Application\Category\Show\Query\ICategoryQuery;
use App\Front\Application\Category\Show\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private ICategoryQuery $categories, private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->categories->find($input->slug), $this->articles->articles($input->slug));
    }
}
