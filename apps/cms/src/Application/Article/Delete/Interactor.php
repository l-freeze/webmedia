<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Delete;

use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\CmsActor;

final readonly class Interactor
{
    public function __construct(
        private IArticleRepository $articles,
        private ArticlePolicy $articlePolicy,
    ) {
    }

    public function execute(Input $input): Output
    {
        $articleId = new ArticleId($input->id);
        $article = $this->articles->findById($articleId);
        if ($article === null || $article->status->value === 'trashed') {
            return new Output(deleted: false, error: '記事が見つからないか、すでに削除されています。');
        }

        $actor = new CmsActor(id: $input->currentUser->id, role: $input->currentUser->role);
        $permission = $this->articlePolicy->authorizeDelete($actor);
        $trashedArticle = $article->trash($permission);
        $this->articles->save($trashedArticle);

        return new Output(deleted: true);
    }
}
