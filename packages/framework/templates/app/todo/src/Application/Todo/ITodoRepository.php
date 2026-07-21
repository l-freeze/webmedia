<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Todo;

interface ITodoRepository
{
    /** @return list<TodoItem> */
    public function all(): array;

    public function add(string $title): void;

    public function complete(string $id): void;
}
