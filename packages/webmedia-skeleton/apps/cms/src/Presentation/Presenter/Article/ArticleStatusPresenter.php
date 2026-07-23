<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Presenter\Article;

use App\Shared\Time\Application\IClock;
use DateTimeImmutable;
use DateTimeZone;

final readonly class ArticleStatusPresenter
{
    public function __construct(private IClock $clock)
    {
    }

    public function label(
        string $status,
        ?string $publishedAt = null,
        ?string $publishedEndsAt = null,
    ): string {
        if ($status === 'published') {
            $now = $this->clock->now();
            if ($publishedAt === null || $publishedAt === '') {
                return '表示（公開日時未設定）';
            }
            if (new DateTimeImmutable($publishedAt, new DateTimeZone('UTC')) > $now) {
                return '公開予約';
            }
            if ($publishedEndsAt !== null && $publishedEndsAt !== '' && new DateTimeImmutable($publishedEndsAt, new DateTimeZone('UTC')) <= $now) {
                return '公開終了';
            }
            return '公開中';
        }

        return match ($status) {
            'private' => '非表示',
            'trashed' => 'ゴミ箱',
            default => '下書き',
        };
    }
}
