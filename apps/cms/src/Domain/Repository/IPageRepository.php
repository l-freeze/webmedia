<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface IPageRepository
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void;
}
