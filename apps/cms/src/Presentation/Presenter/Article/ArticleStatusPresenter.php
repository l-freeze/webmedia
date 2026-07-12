<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Presenter\Article;

use DateTimeImmutable;

final readonly class ArticleStatusPresenter
{
    public function label(
        string $status,
        ?string $publishedAt = null,
        ?string $publishedEndsAt = null,
        ?DateTimeImmutable $now = null,
    ): string {
        if ($status === 'published') {
            $now ??= new DateTimeImmutable();
            if ($publishedAt === null || $publishedAt === '') {
                return '表示（公開日時未設定）';
            }
            if (new DateTimeImmutable($publishedAt) > $now) {
                return '公開予約';
            }
            if ($publishedEndsAt !== null && $publishedEndsAt !== '' && new DateTimeImmutable($publishedEndsAt) <= $now) {
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
