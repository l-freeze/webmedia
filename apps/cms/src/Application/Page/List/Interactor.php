<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\List;

use App\Cms\Application\Page\List\Query\IPageQuery;

final readonly class Interactor
{
    public function __construct(private IPageQuery $pages)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->pages->list());
    }
}
