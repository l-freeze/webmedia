<?php

declare(strict_types=1);

namespace App\Cms\Application\ArticleVersion\Preview;

use App\Cms\Application\ArticleVersion\Preview\Query\IArticleVersionQuery;

final readonly class Interactor
{
    public function __construct(private IArticleVersionQuery $versions)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->versions->find($input->articleId, $input->versionNo));
    }
}
