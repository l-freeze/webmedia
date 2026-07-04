<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Restore;

use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\PageId;
use App\Cms\Domain\Value\PageVersionNo;

final readonly class Interactor
{
    public function __construct(private IPageRepository $pages)
    {
    }

    public function execute(Input $input): Output
    {
        $this->pages->restore(pageId: new PageId($input->pageId), versionNo: new PageVersionNo($input->versionNo));

        return new Output(restored: true);
    }
}
