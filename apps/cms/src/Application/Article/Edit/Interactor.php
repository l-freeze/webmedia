<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Edit;

use App\Cms\Application\Article\Edit\Query\IArticleQuery;
use App\Cms\Application\Article\Edit\Query\ICategoryQuery;
use App\Cms\Application\Article\Edit\Query\IMediaQuery;

final readonly class Interactor
{
    public function __construct(
        private IArticleQuery $articles,
        private ICategoryQuery $categories,
        private IMediaQuery $media,
    )
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(
            article: $input->id === null ? null : $this->articles->find(id: $input->id),
            categories: $this->categories->categories(),
            media: $this->media->media(),
        );
    }
}
