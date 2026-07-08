<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Inquiry\Create;

use App\FrontBusiness\Domain\Repository\IInquiryRepository;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private IInquiryRepository $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        $validation = (new FormValidator(new ValidationInput([
            'name' => $input->name,
            'email' => $input->email,
            'subject' => $input->subject,
            'message' => $input->message,
        ])))
            ->required(field: 'name', label: '名前')
            ->maxLength(field: 'name', label: '名前', max: InquiryInputLimits::NAME_MAX_LENGTH)
            ->required(field: 'email', label: 'メールアドレス')
            ->email(field: 'email', label: 'メールアドレス')
            ->required(field: 'subject', label: '件名')
            ->maxLength(field: 'subject', label: '件名', max: InquiryInputLimits::SUBJECT_MAX_LENGTH)
            ->required(field: 'message', label: '内容')
            ->maxLength(field: 'message', label: '内容', max: InquiryInputLimits::MESSAGE_MAX_LENGTH)
            ->output();

        if (!$validation->isValid()) {
            return new Output(created: false, error: $validation->firstMessage());
        }

        $this->inquiries->create(name: $input->name, email: $input->email, subject: $input->subject, message: $input->message);
        return new Output(created: true);
    }
}
