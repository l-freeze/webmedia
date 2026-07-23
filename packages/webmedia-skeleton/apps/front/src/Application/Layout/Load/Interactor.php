<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Load;

use App\Front\Application\Layout\Load\Query\ICategoryQuery;
use App\Front\Application\Layout\Load\Query\IPageLinkQuery;
use App\Front\Application\Layout\Load\Query\ISiteSettingQuery;

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
        $settings = $this->settings->current();
        $publishedPages = $this->pageLinks->publishedPageLinks();
        $publishedCategories = $this->categories->all();

        return new Output(
            settings: $settings,
            publishedPages: $publishedPages,
            publishedCategories: $publishedCategories,
        );
    }
}
