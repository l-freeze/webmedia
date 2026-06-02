<?php

declare(strict_types=1);

namespace Tests\Front;

use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;
use PHPUnit\Framework\TestCase;

final class ContactValidationTest extends TestCase
{
    public function testEmailValidationRejectsInvalidAddress(): void
    {
        $validation = (new FormValidator(input: new ValidationInput(values: ['email' => 'invalid-address'])))
            ->required(field: 'email', label: 'メールアドレス')
            ->email(field: 'email', label: 'メールアドレス')
            ->output();

        self::assertFalse($validation->isValid());
        self::assertSame('メールアドレスを正しく入力してください。', $validation->firstMessage());
    }

    public function testEmailValidationAcceptsValidAddress(): void
    {
        $validation = (new FormValidator(input: new ValidationInput(values: ['email' => 'reader@example.test'])))
            ->required(field: 'email', label: 'メールアドレス')
            ->email(field: 'email', label: 'メールアドレス')
            ->output();

        self::assertTrue($validation->isValid());
        self::assertNull($validation->firstMessage());
    }

    public function testRequiredValidationRejectsBlankString(): void
    {
        $validation = (new FormValidator(input: new ValidationInput(values: ['name' => '  '])))
            ->required(field: 'name', label: '名前')
            ->output();

        self::assertFalse($validation->isValid());
        self::assertSame('名前を入力してください。', $validation->firstMessage());
    }
}
