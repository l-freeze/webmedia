<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Application\Inquiry\Update\Input;
use App\Cms\Application\Inquiry\Update\Interactor;
use App\Cms\Domain\Entity\InquiryStatusUpdate;
use App\Cms\Domain\Value\InquiryStatus;
use App\Cms\Domain\Repository\IInquiryRepository;
use PHPUnit\Framework\TestCase;

final class InquiryUpdateInteractorTest extends TestCase
{
    public function testUpdatesInquiryStatusAndAdminNote(): void
    {
        $repository = new RecordingInquiryRepository();

        $output = (new Interactor(inquiries: $repository))->execute(input: new Input(
            id: 12,
            status: InquiryStatus::IN_PROGRESS,
            adminNote: '返信準備中',
        ));

        self::assertTrue($output->updated);
        self::assertSame(12, $repository->lastId);
        self::assertSame(InquiryStatus::IN_PROGRESS, $repository->lastStatus);
        self::assertSame('返信準備中', $repository->lastAdminNote);
    }

    public function testRejectsUnknownStatus(): void
    {
        $repository = new RecordingInquiryRepository();

        $output = (new Interactor(inquiries: $repository))->execute(input: new Input(
            id: 12,
            status: 'unknown',
            adminNote: '',
        ));

        self::assertFalse($output->updated);
        self::assertSame('状態を選択してください。', $output->validation->firstMessage());
        self::assertNull($repository->lastId);
    }

    public function testRejectsTooLongAdminNote(): void
    {
        $repository = new RecordingInquiryRepository();

        $output = (new Interactor(inquiries: $repository))->execute(input: new Input(
            id: 12,
            status: InquiryStatus::CLOSED,
            adminNote: str_repeat('a', 1001),
        ));

        self::assertFalse($output->updated);
        self::assertSame('管理メモは1000文字以内で入力してください。', $output->validation->firstMessage());
        self::assertNull($repository->lastId);
    }
}

final class RecordingInquiryRepository implements IInquiryRepository
{
    public ?int $lastId = null;
    public ?string $lastStatus = null;
    public ?string $lastAdminNote = null;

    public function updateStatus(InquiryStatusUpdate $update): bool
    {
        $this->lastId = $update->id;
        $this->lastStatus = $update->status;
        $this->lastAdminNote = $update->adminNote;

        return true;
    }
}
