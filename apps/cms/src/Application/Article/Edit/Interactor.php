<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit;

use App\Cms\Application\Article\Edit\Query\IArticleQuery;
use App\Cms\Application\Article\Edit\Query\ICategoryQuery;
use App\Cms\Application\Article\Edit\Query\IImageQuery;

final readonly class Interactor
{
    public function __construct(
        private IArticleQuery $articles,
        private ICategoryQuery $categories,
        private IImageQuery $images,
    )
    {
    }

    public function execute(Input $input): Output
    {
        $article = $input->id === null ? null : $this->articles->find($input->id);
        $categories = $this->categories->categories();
        $media = $this->images->images();

        return new Output(
            article: $article,
            categories: $categories,
            media: $media,
        );
    }
}
