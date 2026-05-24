<?php

declare(strict_types=1);

namespace App\Front\Application\Home\Show;

use App\Front\Application\Home\Show\Query\IArticleQuery;
use App\Front\Application\Home\Show\Query\ICategoryQuery;

final readonly class Interactor
{
    private const ARTICLES_PER_PAGE = 10;

    public function __construct(private IArticleQuery $articles, private ICategoryQuery $categories)
    {
    }

    public function execute(Input $input): Output
    {
        $totalArticles = $this->articles->countPublished()->value;
        $totalPages = max(1, (int) ceil($totalArticles / self::ARTICLES_PER_PAGE));
        $currentPage = min($input->page, $totalPages);
        $offset = ($currentPage - 1) * self::ARTICLES_PER_PAGE;

        return new Output(
            $this->articles->latest(self::ARTICLES_PER_PAGE, $offset),
            $this->categories->all(),
            new Value\Pagination($currentPage, self::ARTICLES_PER_PAGE, $totalArticles),
        );
    }
}
