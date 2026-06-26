<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Update;

use App\Cms\Application\Inquiry\Update\Repository\IInquiryRepository;
use App\Cms\Application\Inquiry\Value\InquiryStatus;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    private const ADMIN_NOTE_MAX_LENGTH = 1000;

    public function __construct(private IInquiryRepository $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        $errors = [];
        if (!InquiryStatus::isValid(status: $input->status)) {
            $errors[] = new ValidationError(field: 'status', message: '状態を選択してください。');
        }
        if (mb_strlen($input->adminNote) > self::ADMIN_NOTE_MAX_LENGTH) {
            $errors[] = new ValidationError(field: 'admin_note', message: '管理メモは1000文字以内で入力してください。');
        }
        if ($errors !== []) {
            return Output::invalid(validation: ValidationOutput::invalid(...$errors));
        }

        return $this->inquiries->updateStatus(id: $input->id, status: $input->status, adminNote: $input->adminNote)
            ? Output::updated()
            : Output::invalid(validation: ValidationOutput::invalid(new ValidationError(field: 'id', message: '問い合わせが見つかりません。')));
    }
}
