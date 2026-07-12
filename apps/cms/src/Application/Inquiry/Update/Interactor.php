<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Update;

use App\Cms\Domain\Entity\InquiryStatusUpdate;
use App\Cms\Domain\Value\InquiryStatus;
use App\Cms\Domain\Repository\IInquiryRepository;
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
        $status = InquiryStatus::tryFrom($input->status);
        if ($status === null) {
            $errors[] = new ValidationError(field: 'status', message: '状態を選択してください。');
        }
        if (mb_strlen($input->adminNote) > self::ADMIN_NOTE_MAX_LENGTH) {
            $errors[] = new ValidationError(field: 'admin_note', message: '管理メモは1000文字以内で入力してください。');
        }
        if ($errors !== []) {
            return Output::invalid(ValidationOutput::invalid(...$errors));
        }

        $statusUpdate = new InquiryStatusUpdate(
            id: $input->id,
            status: $status,
            adminNote: $input->adminNote,
        );
        $updated = $this->inquiries->updateStatus($statusUpdate);

        if (!$updated) {
            return Output::invalid(ValidationOutput::invalid(new ValidationError(field: 'id', message: '問い合わせが見つかりません。')));
        }

        return Output::updated();
    }
}
