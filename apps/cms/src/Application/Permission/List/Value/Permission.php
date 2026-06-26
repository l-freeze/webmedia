<?php

declare(strict_types=1);

namespace App\Cms\Application\Permission\List\Value;

final readonly class Permission
{
    /** @param list<string> $roles */
    public function __construct(public string $code, public array $roles)
    {
    }

    /** @return array{code:string,roles:list<string>} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'roles' => $this->roles,
        ];
    }
}
