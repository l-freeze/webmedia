<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Todo;

final readonly class TodoItem
{
    public function __construct(
        public string $id,
        public string $title,
        public bool $completed,
        public string $createdAt,
    ) {
    }

    /** @return array{id:string,title:string,completed:bool,created_at:string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'completed' => $this->completed,
            'created_at' => $this->createdAt,
        ];
    }
}
