<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Restore;

use App\Cms\Domain\Policy\ArticlePolicy;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\ArticleVersionNo;
use App\Cms\Domain\Value\CmsActor;
use App\Cms\Domain\Value\CmsRole;

final readonly class Interactor
{
    public function __construct(
        private IArticleRepository $articles,
        private ArticlePolicy $articlePolicy,
    ) {
    }

    public function execute(Input $input): Output
    {
        $articleId = new ArticleId($input->articleId);
        $versionNo = new ArticleVersionNo($input->versionNo);
        $article = $this->articles->findById($articleId);
        $version = $this->articles->findVersion(articleId: $articleId, versionNo: $versionNo);
        if ($article === null || $version === null) {
            return new Output(restored: false);
        }

        $actor = new CmsActor(
            id: new AdminUserId(value: $input->currentUser->id),
            role: CmsRole::from($input->currentUser->role),
        );
        $restoredArticle = $article->restore(
            permission: $this->articlePolicy->authorizeRestore($actor),
            version: $version,
        );
        $this->articles->save($restoredArticle);

        return new Output(restored: true);
    }
}
