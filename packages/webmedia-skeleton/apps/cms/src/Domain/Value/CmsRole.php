<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum CmsRole: string
{
    case Viewer = 'viewer';
    case Writer = 'writer';
    case Editor = 'editor';
    case Admin = 'admin';

    public function isAtLeast(self $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Viewer => 10,
            self::Writer => 20,
            self::Editor => 30,
            self::Admin => 40,
        };
    }
}
