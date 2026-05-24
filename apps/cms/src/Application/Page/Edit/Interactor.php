<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit;

use App\Cms\Application\Page\Edit\Query\IPageQuery;

final readonly class Interactor
{
    public function __construct(private IPageQuery $pages)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($input->id === null ? null : $this->pages->find($input->id));
    }
}
