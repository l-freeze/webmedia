<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\List;

use App\Cms\Application\Article\List\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(keyword: $input->keyword, articles: $this->articles->list($input->keyword));
    }
}
