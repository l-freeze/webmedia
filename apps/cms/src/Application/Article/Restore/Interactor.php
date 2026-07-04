<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Restore;

use App\Cms\Application\Auth\Policy\ArticlePolicy;
use App\Cms\Application\Auth\Policy\Exception\AuthorizationException;
use App\Cms\Application\Auth\Policy\Value\PermissionCode;
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
        if (!$this->articlePolicy->canRestore($input->currentUser)) {
            throw new AuthorizationException(userId: $input->currentUser->id, permission: PermissionCode::ARTICLE_RESTORE);
        }

        $this->articles->restore(articleId: new ArticleId($input->articleId), versionNo: new ArticleVersionNo($input->versionNo));
        return new Output(restored: true);
    }
}
