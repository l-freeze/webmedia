<?php

declare(strict_types=1);

namespace App\Cms\Auth\Domain;

interface UserRepository
{
    /** @return array{id:int,email:string,password_hash:string,name:string,role:string}|null */
    public function findByEmail(string $email): ?array;
}
