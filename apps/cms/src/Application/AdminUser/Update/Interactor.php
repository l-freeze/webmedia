<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Update;

use App\Cms\Application\Auth\Policy\Value\RoleCode;
use App\Cms\Application\AdminUser\Update\Port\PasswordHasher;
use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;

final readonly class Interactor
{
    private const NAME_MAX_LENGTH = 120;
    private const EMAIL_MAX_LENGTH = 190;
    private const PASSWORD_MIN_LENGTH = 8;

    public function __construct(
        private IAdminUserRepository $users,
        private PasswordHasher $passwords,
    ) {
    }

    public function execute(Input $input): Output
    {
        $currentRole = $this->users->roleOf($input->id);
        if ($currentRole === null) {
            return new Output(updated: false, error: '管理者アカウントが見つかりません。');
        }

        $values = new ValidationInput($input->data);
        $validation = (new FormValidator($values))
            ->required(field: 'email', label: 'メールアドレス')
            ->email(field: 'email', label: 'メールアドレス')
            ->maxLength(field: 'email', label: 'メールアドレス', max: self::EMAIL_MAX_LENGTH)
            ->required(field: 'name', label: '名前')
            ->maxLength(field: 'name', label: '名前', max: self::NAME_MAX_LENGTH)
            ->output();

        if (!$validation->isValid()) {
            return new Output(updated: false, error: $validation->firstMessage());
        }

        $role = $values->string(field: 'role');
        if (!in_array($role, RoleCode::all(), true)) {
            return new Output(updated: false, error: 'ロールを正しく選択してください。');
        }

        if ($currentRole === RoleCode::ADMIN && $role !== RoleCode::ADMIN && $this->users->adminCount() <= 1) {
            return new Output(updated: false, error: '最後のadminロールは変更できません。');
        }

        $email = $values->string(field: 'email');
        if ($this->users->emailExists(email: $email, exceptId: $input->id)) {
            return new Output(updated: false, error: 'このメールアドレスは既に使われています。');
        }

        $password = $values->string(field: 'password');
        $passwordHash = '';
        if ($password !== '') {
            if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
                return new Output(updated: false, error: 'パスワードは8文字以上で入力してください。');
            }
            $passwordHash = $this->passwords->hash($password);
        }

        return new Output(updated: $this->users->update(user: new AdminUser(
            id: $input->id,
            email: $email,
            name: $values->string(field: 'name'),
            role: $role,
            passwordHash: $passwordHash,
        )));
    }
}
