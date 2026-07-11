<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Page\Show;

use App\FrontBusiness\Application\Page\Show\Query\IPageQuery;

final readonly class Interactor
{
    public function __construct(private IPageQuery $pages)
    {
    }

    public function execute(Input $input): Output
    {
        $page = $this->pages->findPublishedBySlug($input->slug);

        return new Output(page: $page);
    }
}
