<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Delete;

use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\CmsRole;

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

        $userId = new AdminUserId(value: $input->id);
        $role = $this->users->roleOf($userId);
        if ($role === null) {
            return new Output(deleted: false, error: '管理者アカウントが見つかりません。');
        }

        $adminCount = $this->users->adminCount();
        if ($role === CmsRole::Admin && $adminCount <= 1) {
            return new Output(deleted: false, error: '最後のadminロールは削除できません。');
        }

        $deleted = $this->users->delete($userId);

        return new Output(deleted: $deleted);
    }
}
