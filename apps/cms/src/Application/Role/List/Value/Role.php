<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List\Value;

final readonly class Role
{
    /** @param list<Permission> $permissions */
    public function __construct(public string $code, public array $permissions)
    {
    }

    /** @return array{code:string,permissions:list<string>} */
    public function toArray(): array
    {
        $permissions = [];
        foreach ($this->permissions as $permission) {
            $permissions[] = $permission->code;
        }

        return [
            'code' => $this->code,
            'permissions' => $permissions,
        ];
    }
}
