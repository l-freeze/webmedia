<?php

declare(strict_types=1);

namespace App\Front\Presentation\Presenter;

use DateTimeImmutable;
use DateTimeZone;

final readonly class ArticleDatePresenter
{
    /**
     * @param array<string, mixed> $article
     * @return array<string, mixed>
     */
    public function present(array $article): array
    {
        foreach (['published_at', 'updated_at'] as $key) {
            $value = $article[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $article[$key] = (new DateTimeImmutable($value, new DateTimeZone('UTC')))
                    ->setTimezone(new DateTimeZone('Asia/Tokyo'))
                    ->format('Y-m-d');
            }
        }

        return $article;
    }
}
