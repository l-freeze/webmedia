<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginAttempts extends AbstractMigration
{
    public function change(): void
    {
        $this->table('login_attempts')
            ->addColumn('email_hash', 'string', ['limit' => 64])
            ->addColumn('ip_address', 'string', ['limit' => 45])
            ->addColumn('result', 'string', ['limit' => 20])
            ->addColumn('attempted_at', 'datetime')
            ->addIndex(['email_hash', 'ip_address', 'attempted_at'])
            ->addIndex(['ip_address', 'attempted_at'])
            ->create();
    }
}
