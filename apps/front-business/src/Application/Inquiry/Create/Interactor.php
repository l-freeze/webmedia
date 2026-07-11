<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Inquiry\Create;

use App\FrontBusiness\Domain\Entity\Inquiry;
use App\FrontBusiness\Domain\Repository\IInquiryRepository;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private IInquiryRepository $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        $validation = $this->validate($input);

        if (!$validation->isValid()) {
            return new Output(created: false, error: $validation->firstMessage());
        }

        $inquiry = new Inquiry(
            name: $input->name,
            email: $input->email,
            subject: $input->subject,
            message: $input->message,
        );
        $this->inquiries->create($inquiry);

        return new Output(created: true);
    }

    private function validate(Input $input): ValidationOutput
    {
        return ValidationOutput::invalid(...array_values(array_filter([
            self::required(field: 'name', label: '名前', value: $input->name),
            self::maxLength(field: 'name', label: '名前', value: $input->name, max: InquiryInputLimits::NAME_MAX_LENGTH),
            self::required(field: 'email', label: 'メールアドレス', value: $input->email),
            self::email(field: 'email', label: 'メールアドレス', value: $input->email),
            self::required(field: 'subject', label: '件名', value: $input->subject),
            self::maxLength(field: 'subject', label: '件名', value: $input->subject, max: InquiryInputLimits::SUBJECT_MAX_LENGTH),
            self::required(field: 'message', label: '内容', value: $input->message),
            self::maxLength(field: 'message', label: '内容', value: $input->message, max: InquiryInputLimits::MESSAGE_MAX_LENGTH),
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
