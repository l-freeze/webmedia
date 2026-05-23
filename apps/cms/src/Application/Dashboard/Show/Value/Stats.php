<?php

declare(strict_types=1);

namespace App\Cms\Application\Dashboard\Show\Value;

final readonly class Stats
{
    public function __construct(
        public int $published,
        public int $draft,
        public int $scheduled,
        public int $inquiries,
    ) {
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            '公開中の記事' => $this->published,
            '下書き' => $this->draft,
            '予約投稿' => $this->scheduled,
            '未対応問い合わせ' => $this->inquiries,
        ];
    }
}
