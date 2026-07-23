<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\CmsLoginAttemptReset;
use App\Cms\Domain\Repository\ICmsLoginAttemptResetRepository;
use App\Cms\Domain\Value\CmsLoginAttemptResetScope;
use App\Shared\Database\PdoHelper;
use PDO;

final readonly class PdoCmsLoginAttemptResetRepository implements ICmsLoginAttemptResetRepository
{
    public function __construct(private PDO $pdo, private LoginAttemptEmailHash $emailHash)
    {
    }

    public function save(CmsLoginAttemptReset $reset): void
    {
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: <<<'SQL'
INSERT INTO cms_login_attempt_resets (
  scope,
  email_hash,
  ip_address,
  reset_at,
  reset_by_user_id,
  reason
) VALUES (
  :scope,
  :email_hash,
  :ip_address,
  :reset_at,
  :reset_by_user_id,
  :reason
)
SQL,
        );
        PdoHelper::execute(statement: $stmt, params: [
            'scope' => $reset->scope->value,
            'email_hash' => $this->emailHash(reset: $reset),
            'ip_address' => $reset->ipAddress->value,
            'reset_at' => $reset->resetAt->value->format('Y-m-d H:i:s'),
            'reset_by_user_id' => $reset->resetByUserId->value,
            'reason' => $reset->reason->value,
        ]);
    }

    private function emailHash(CmsLoginAttemptReset $reset): ?string
    {
        if ($reset->scope !== CmsLoginAttemptResetScope::EmailIp || $reset->email === null) {
            return null;
        }

        return $this->emailHash->hash(email: $reset->email->value);
    }
}
