<?php

declare(strict_types=1);

namespace Tests\Front;

use App\Front\Application\Inquiry\Create\Input;
use App\Front\Application\Inquiry\Create\Interactor;
use App\Front\Domain\Repository\IInquiryRepository;
use PHPUnit\Framework\TestCase;

final class InquiryCreateInteractorTest extends TestCase
{
    public function testRejectsTooLongName(): void
    {
        $output = (new Interactor(inquiries: new RecordingInquiryRepository()))->execute(input: new Input(
            name: str_repeat('あ', 17),
            email: 'test@example.com',
            subject: '件名',
            message: '本文',
        ));

        self::assertFalse($output->created);
        self::assertSame('名前は16文字以内で入力してください。', $output->error);
    }

    public function testRejectsTooLongSubject(): void
    {
        $output = (new Interactor(inquiries: new RecordingInquiryRepository()))->execute(input: new Input(
            name: '山田太郎',
            email: 'test@example.com',
            subject: str_repeat('a', 101),
            message: '本文',
        ));

        self::assertFalse($output->created);
        self::assertSame('件名は100文字以内で入力してください。', $output->error);
    }

    public function testRejectsTooLongMessage(): void
    {
        $output = (new Interactor(inquiries: new RecordingInquiryRepository()))->execute(input: new Input(
            name: '山田太郎',
            email: 'test@example.com',
            subject: '件名',
            message: str_repeat('a', 1001),
        ));

        self::assertFalse($output->created);
        self::assertSame('内容は1000文字以内で入力してください。', $output->error);
    }
}

final class RecordingInquiryRepository implements IInquiryRepository
{
    public function create(string $name, string $email, string $subject, string $message): void
    {
    }
}
