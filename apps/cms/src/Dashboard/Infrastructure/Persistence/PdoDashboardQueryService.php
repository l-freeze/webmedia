<?php

declare(strict_types=1);

namespace App\Cms\Dashboard\Infrastructure\Persistence;

use App\Cms\Article\Domain\ArticleRepository;
use App\Cms\Dashboard\Query\ShowDashboard\DashboardQueryService;

final readonly class PdoDashboardQueryService implements DashboardQueryService
{
    public function __construct(private ArticleRepository $articles)
    {
    }

    public function stats(): array
    {
        return $this->articles->stats();
    }
}
