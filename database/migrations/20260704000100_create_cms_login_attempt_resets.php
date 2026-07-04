<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCmsLoginAttemptResets extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('login_attempts') && !$this->hasTable('cms_login_attempts')) {
            $this->table('login_attempts')->rename('cms_login_attempts')->update();
        }

        $this->table('cms_login_attempt_resets')
            ->addColumn('scope', 'string', ['limit' => 20])
            ->addColumn('email_hash', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45])
            ->addColumn('reset_at', 'datetime')
            ->addColumn('reset_by_user_id', 'integer')
            ->addColumn('reason', 'string', ['limit' => 200, 'default' => ''])
            ->addIndex(['scope', 'email_hash', 'ip_address', 'reset_at'])
            ->addIndex(['scope', 'ip_address', 'reset_at'])
            ->create();
    }
}
