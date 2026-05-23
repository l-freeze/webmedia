<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Search;

use App\Front\Application\Article\Search\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($input->keyword, $input->keyword === '' ? [] : $this->articles->search($input->keyword));
    }
}
