<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\List\Value;

final readonly class AdminUser
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public string $role,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @return array{id:int,email:string,name:string,role:string,created_at:string,updated_at:string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
