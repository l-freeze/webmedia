<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\Show;

use App\FrontBusiness\Application\Category\Show\Query\ICategoryQuery;
use App\FrontBusiness\Application\Category\Show\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private ICategoryQuery $categories, private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $category = $this->categories->find($input->slug);
        $articles = $this->articles->articles($input->slug);

        return new Output(category: $category, articles: $articles);
    }
}
