<?php

declare(strict_types=1);

namespace Tests\Cms\Auth;

use App\Cms\Application\Auth\ResetLoginAttempt\Input;
use App\Cms\Application\Auth\ResetLoginAttempt\Interactor;
use App\Cms\Domain\Entity\CmsLoginAttemptReset;
use App\Cms\Domain\Repository\ICmsLoginAttemptResetRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Shared\Time\FrozenClock;

final class ResetLoginAttemptInteractorTest extends TestCase
{
    public function testUsesClockTimeForResetEvent(): void
    {
        $resets = new RecordingLoginAttemptResetRepository();
        $now = new DateTimeImmutable('2026-07-13 00:15:00 UTC');
        $interactor = new Interactor(resets: $resets, clock: new FrozenClock($now));

        $output = $interactor->execute(Input::create(
            scope: 'ip',
            email: '',
            ipAddress: '192.0.2.10',
            reason: 'manual reset',
            resetByUserId: 1,
        ));

        self::assertTrue($output->reset);
        self::assertSame($now, $resets->lastReset?->resetAt->value);
    }
}

final class RecordingLoginAttemptResetRepository implements ICmsLoginAttemptResetRepository
{
    public ?CmsLoginAttemptReset $lastReset = null;

    public function save(CmsLoginAttemptReset $reset): void
    {
        $this->lastReset = $reset;
    }
}
