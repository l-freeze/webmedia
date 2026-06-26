<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List\Value;

final readonly class Role
{
    /** @param list<string> $permissions */
    public function __construct(public string $code, public array $permissions)
    {
    }

    /** @return array{code:string,permissions:list<string>} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'permissions' => $this->permissions,
        ];
    }
}
