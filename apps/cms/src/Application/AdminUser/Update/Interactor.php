<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Update;

use App\Cms\Application\AdminUser\Update\Port\PasswordHasher;
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
        private PasswordHasher $passwords,
    ) {
    }

    public function execute(Input $input): Output
    {
        $currentRole = $this->users->roleOf($input->id);
        if ($currentRole === null) {
            return new Output(updated: false, error: '管理者アカウントが見つかりません。');
        }

        $validation = $this->validate($input);

        if (!$validation->isValid()) {
            return new Output(updated: false, error: $validation->firstMessage());
        }

        if (!in_array($input->role, CmsRole::all(), true)) {
            return new Output(updated: false, error: 'ロールを正しく選択してください。');
        }

        $adminCount = $this->users->adminCount();
        if ($currentRole === CmsRole::ADMIN && $input->role !== CmsRole::ADMIN && $adminCount <= 1) {
            return new Output(updated: false, error: '最後のadminロールは変更できません。');
        }

        $emailExists = $this->users->emailExists(email: $input->email, exceptId: $input->id);
        if ($emailExists) {
            return new Output(updated: false, error: 'このメールアドレスは既に使われています。');
        }

        $passwordHash = '';
        if ($input->password !== '') {
            if (strlen($input->password) < self::PASSWORD_MIN_LENGTH) {
                return new Output(updated: false, error: 'パスワードは8文字以上で入力してください。');
            }
            $passwordHash = $this->passwords->hash($input->password);
        }

        $user = new AdminUser(
            id: $input->id,
            email: $input->email,
            name: $input->name,
            role: $input->role,
            passwordHash: $passwordHash,
        );
        $updated = $this->users->update(user: $user);

        return new Output(updated: $updated);
    }

    private function validate(Input $input): ValidationOutput
    {
        return ValidationOutput::invalid(...array_values(array_filter([
            self::required(field: 'email', label: 'メールアドレス', value: $input->email),
            self::email(field: 'email', label: 'メールアドレス', value: $input->email),
            self::maxLength(field: 'email', label: 'メールアドレス', value: $input->email, max: self::EMAIL_MAX_LENGTH),
            self::required(field: 'name', label: '名前', value: $input->name),
            self::maxLength(field: 'name', label: '名前', value: $input->name, max: self::NAME_MAX_LENGTH),
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
