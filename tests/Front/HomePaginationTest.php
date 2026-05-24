<?php

declare(strict_types=1);

namespace Tests\Front;

use App\Front\Application\Home\Show\Input;
use App\Front\Application\Home\Show\Interactor;
use App\Front\Application\Home\Show\Query\IArticleQuery;
use App\Front\Application\Home\Show\Query\ICategoryQuery;
use App\Front\Application\Home\Show\Value\Article;
use App\Front\Application\Home\Show\Value\ArticleCount;
use App\Front\Application\Home\Show\Value\Category;
use PHPUnit\Framework\TestCase;

final class HomePaginationTest extends TestCase
{
    public function testHomeUsesTenArticlesPerPage(): void
    {
        $articles = new class implements IArticleQuery {
            public int $limit = 0;
            public int $offset = 0;

            /** @return array<int, Article> */
            public function latest(int $limit, int $offset): array
            {
                $this->limit = $limit;
                $this->offset = $offset;

                return [];
            }

            public function countPublished(): ArticleCount
            {
                return new ArticleCount(21);
            }
        };
        $categories = new class implements ICategoryQuery {
            /** @return array<int, Category> */
            public function all(): array
            {
                return [];
            }
        };

        $output = (new Interactor($articles, $categories))->execute(new Input(2));

        self::assertSame(10, $articles->limit);
        self::assertSame(10, $articles->offset);
        self::assertSame(2, $output->pagination->currentPage);
        self::assertSame(3, $output->pagination->totalPages);
    }
}
