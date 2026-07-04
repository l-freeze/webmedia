<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Delete;

use App\Cms\Application\Auth\Authorization\Value\RoleCode;
use App\Cms\Domain\Repository\IAdminUserRepository;

final readonly class Interactor
{
    public function __construct(private IAdminUserRepository $users)
    {
    }

    public function execute(Input $input): Output
    {
        if ($input->id === $input->currentUser->id) {
            return new Output(deleted: false, error: 'ログイン中の自分自身は削除できません。');
        }

        $role = $this->users->roleOf($input->id);
        if ($role === null) {
            return new Output(deleted: false, error: '管理者アカウントが見つかりません。');
        }

        if ($role === RoleCode::ADMIN && $this->users->adminCount() <= 1) {
            return new Output(deleted: false, error: '最後のadminロールは削除できません。');
        }

        return new Output(deleted: $this->users->delete($input->id));
    }
}
