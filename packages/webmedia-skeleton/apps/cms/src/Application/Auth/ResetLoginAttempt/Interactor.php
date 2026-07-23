<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\ResetLoginAttempt;

use App\Cms\Domain\Entity\CmsLoginAttemptReset;
use App\Cms\Domain\Repository\ICmsLoginAttemptResetRepository;
use App\Cms\Domain\Value\CmsLoginAttemptResetScope;
use App\Cms\Domain\Value\AdminUserId;
use App\Cms\Domain\Value\IpAddress;
use App\Cms\Domain\Value\LoginAttemptEmail;
use App\Cms\Domain\Value\LoginAttemptResetAt;
use App\Cms\Domain\Value\LoginAttemptResetReason;
use App\Shared\Time\Application\IClock;

final readonly class Interactor
{
    private const REASON_MAX_LENGTH = 200;

    public function __construct(private ICmsLoginAttemptResetRepository $resets, private IClock $clock)
    {
    }

    public function execute(Input $input): Output
    {
        $scope = CmsLoginAttemptResetScope::tryFrom($input->scope);
        if ($scope === null) {
            return Output::invalid('解除対象を選択してください。');
        }

        $ipAddress = trim($input->ipAddress);
        if ($ipAddress === '' || filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return Output::invalid('IPアドレスを正しく入力してください。');
        }

        $reason = mb_substr(trim($input->reason), 0, self::REASON_MAX_LENGTH);
        $now = $this->clock->now();
        $resetIpAddress = new IpAddress(value: $ipAddress);
        $resetAt = new LoginAttemptResetAt(value: $now);
        $resetByUserId = new AdminUserId(value: $input->resetByUserId);
        $resetReason = new LoginAttemptResetReason(value: $reason);

        if ($scope === CmsLoginAttemptResetScope::EmailIp) {
            $email = trim($input->email);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return Output::invalid('メールアドレスを正しく入力してください。');
            }

            $reset = CmsLoginAttemptReset::emailIp(
                email: new LoginAttemptEmail(value: $email),
                ipAddress: $resetIpAddress,
                resetAt: $resetAt,
                resetByUserId: $resetByUserId,
                reason: $resetReason,
            );
            $this->resets->save($reset);

            return Output::reset();
        }

        $reset = CmsLoginAttemptReset::ip(
            ipAddress: $resetIpAddress,
            resetAt: $resetAt,
            resetByUserId: $resetByUserId,
            reason: $resetReason,
        );
        $this->resets->save($reset);

        return Output::reset();
    }
}
