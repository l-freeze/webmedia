<?php

declare(strict_types=1);

namespace App\Front\Application\Page\Show;

use App\Front\Application\Page\Show\Query\IPageQuery;

final readonly class Interactor
{
    public function __construct(private IPageQuery $pages)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(page: $this->pages->findPublishedBySlug($input->slug));
    }
}
