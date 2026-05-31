<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Preview;

use App\Cms\Application\Article\Preview\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(article: $this->articles->find(id: $input->id));
    }
}
