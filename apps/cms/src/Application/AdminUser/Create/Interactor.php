<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Create;

use App\Cms\Application\AdminUser\Create\Port\IPasswordHasher;
use App\Cms\Domain\Entity\AdminUser;
use App\Cms\Domain\Repository\IAdminUserRepository;
use App\Cms\Domain\Value\CmsRole;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    private const NAME_MAX_LENGTH = 120;
    private const EMAIL_MAX_LENGTH = 190;
    private const PASSWORD_MIN_LENGTH = 8;

    public function __construct(
        private IAdminUserRepository $users,
        private IPasswordHasher $passwords,
    ) {
    }

    public function execute(Input $input): Output
    {
        $validation = $this->validate($input);

        if (!$validation->isValid()) {
            return new Output(created: false, error: $validation->firstMessage());
        }

        if (strlen($input->password) < self::PASSWORD_MIN_LENGTH) {
            return new Output(created: false, error: 'パスワードは8文字以上で入力してください。');
        }

        $role = CmsRole::tryFrom($input->role);
        if ($role === null) {
            return new Output(created: false, error: 'ロールを正しく選択してください。');
        }

        $emailExists = $this->users->emailExists(email: $input->email, exceptId: null);
        if ($emailExists) {
            return new Output(created: false, error: 'このメールアドレスは既に使われています。');
        }

        $user = new AdminUser(
            id: null,
            email: $input->email,
            name: $input->name,
            role: $role,
            passwordHash: $this->passwords->hash($input->password),
        );
        $userId = $this->users->create(user: $user);

        return new Output(created: true, userId: $userId->value);
    }

    private function validate(Input $input): ValidationOutput
    {
        return ValidationOutput::invalid(...array_values(array_filter([
            self::required(field: 'email', label: 'メールアドレス', value: $input->email),
            self::email(field: 'email', label: 'メールアドレス', value: $input->email),
            self::maxLength(field: 'email', label: 'メールアドレス', value: $input->email, max: self::EMAIL_MAX_LENGTH),
            self::required(field: 'name', label: '名前', value: $input->name),
            self::maxLength(field: 'name', label: '名前', value: $input->name, max: self::NAME_MAX_LENGTH),
            self::required(field: 'password', label: 'パスワード', value: $input->password),
        ])));
    }

    private static function required(string $field, string $label, string $value): ?ValidationError
    {
        return $value === '' ? new ValidationError(field: $field, message: "{$label}を入力してください。") : null;
    }

    private static function email(string $field, string $label, string $value): ?ValidationError
    {
        return $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false
            ? new ValidationError(field: $field, message: "{$label}を正しく入力してください。")
            : null;
    }

    private static function maxLength(string $field, string $label, string $value, int $max): ?ValidationError
    {
        return $value !== '' && mb_strlen($value) > $max
            ? new ValidationError(field: $field, message: "{$label}は{$max}文字以内で入力してください。")
            : null;
    }
}
