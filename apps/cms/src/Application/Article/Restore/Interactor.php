<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Restore;

use App\Cms\Application\Article\Policy\ArticlePolicy;
use App\Cms\Application\Auth\Authorization\AuthorizationException;
use App\Cms\Application\Auth\Authorization\Permission;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;

final readonly class Interactor
{
    public function __construct(
        private IArticleRepository $articles,
        private ArticlePolicy $articlePolicy,
    ) {
    }

    public function execute(Input $input): Output
    {
        if (!$this->articlePolicy->canRestore(user: $input->currentUser)) {
            throw new AuthorizationException(userId: $input->currentUser->id, permission: Permission::ARTICLE_RESTORE);
        }

        $this->articles->restore(articleId: new ArticleId(value: $input->articleId), versionNo: new ArticleVersionNo(value: $input->versionNo));
        return new Output(restored: true);
    }
}
