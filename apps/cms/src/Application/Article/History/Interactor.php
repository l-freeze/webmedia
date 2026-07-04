<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\History;

use App\Cms\Application\Article\History\Query\IArticleHistoryQuery;

final readonly class Interactor
{
    public function __construct(private IArticleHistoryQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(articleId: $input->articleId, versions: $this->articles->versions($input->articleId));
    }
}
