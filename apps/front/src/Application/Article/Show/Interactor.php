<?php

declare(strict_types=1);

namespace App\Front\Application\Article\Show;

use App\Front\Application\Article\Show\Query\IArticleQuery;

final readonly class Interactor
{
    public function __construct(private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $article = $this->articles->findPublishedBySlug(slug: $input->slug);
        if ($article === null) {
            return new Output(article: null);
        }

        return new Output(article: $article, related: $this->articles->related(articleId: $article->id, categoryId: $article->categoryId));
    }
}
