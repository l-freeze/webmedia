<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Layout\Load;

use App\FrontBusiness\Application\Layout\Load\Query\ICategoryQuery;
use App\FrontBusiness\Application\Layout\Load\Query\IPageLinkQuery;
use App\FrontBusiness\Application\Layout\Load\Query\ISiteSettingQuery;

final readonly class Interactor
{
    public function __construct(
        private IPageLinkQuery $pageLinks,
        private ISiteSettingQuery $settings,
        private ICategoryQuery $categories,
    ) {
    }

    public function execute(Input $input): Output
    {
        return new Output(
            settings: $this->settings->current(),
            publishedPages: $this->pageLinks->publishedPageLinks(),
            publishedCategories: $this->categories->all(),
        );
    }
}
