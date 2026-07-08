<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Article\Search;

use App\FrontBusiness\Application\Article\Search\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(keyword: $input->keyword, articles: $input->keyword === '' ? [] : $this->articles->search($input->keyword));
    }
}
