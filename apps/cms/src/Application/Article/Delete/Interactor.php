<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Delete;

use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;

final readonly class Interactor
{
    public function __construct(private IArticleRepository $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $deleted = $this->articles->trash(articleId: new ArticleId(value: $input->id));
        if (!$deleted) {
            return new Output(deleted: false, error: '記事が見つからないか、すでに削除されています。');
        }

        return new Output(deleted: true);
    }
}
