<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Restore;

use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;

final readonly class Interactor
{
    public function __construct(private IArticleRepository $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $this->articles->restore(articleId: new ArticleId(value: $input->articleId), versionNo: new ArticleVersionNo(value: $input->versionNo));
        return new Output(restored: true);
    }
}
