<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Create;

use App\Cms\Application\AdminUser\Create\Port\PasswordHasher;
use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Cms\Domain\Value\CmsRole;
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
        $values = new ValidationInput($input->data);
        $validation = (new FormValidator($values))
            ->required(field: 'email', label: 'メールアドレス')
            ->email(field: 'email', label: 'メールアドレス')
            ->maxLength(field: 'email', label: 'メールアドレス', max: self::EMAIL_MAX_LENGTH)
            ->required(field: 'name', label: '名前')
            ->maxLength(field: 'name', label: '名前', max: self::NAME_MAX_LENGTH)
            ->required(field: 'password', label: 'パスワード')
            ->output();

        if (!$validation->isValid()) {
            return new Output(created: false, error: $validation->firstMessage());
        }

        $password = $values->string(field: 'password');
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            return new Output(created: false, error: 'パスワードは8文字以上で入力してください。');
        }

        $role = $values->string(field: 'role');
        if (!in_array($role, CmsRole::all(), true)) {
            return new Output(created: false, error: 'ロールを正しく選択してください。');
        }

        $email = $values->string(field: 'email');
        if ($this->users->emailExists(email: $email, exceptId: null)) {
            return new Output(created: false, error: 'このメールアドレスは既に使われています。');
        }

        $userId = $this->users->create(user: new AdminUser(
            id: null,
            email: $email,
            name: $values->string(field: 'name'),
            role: $role,
            passwordHash: $this->passwords->hash($password),
        ));

        return new Output(created: true, userId: $userId);
    }
}
