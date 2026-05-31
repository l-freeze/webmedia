<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\History;

use App\Cms\Application\Page\History\Query\IPageHistoryQuery;

final readonly class Interactor
{
    public function __construct(private IPageHistoryQuery $pages)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(pageId: $input->pageId, versions: $this->pages->versions(pageId: $input->pageId));
    }
}
