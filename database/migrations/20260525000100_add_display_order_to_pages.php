<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDisplayOrderToPages extends AbstractMigration
{
    public function up(): void
    {
        $pages = $this->table('pages');
        if (!$pages->hasColumn('display_order')) {
            $pages->addColumn('display_order', 'integer', ['default' => 0])->update();
        }

        $pageVersions = $this->table('page_versions');
        if (!$pageVersions->hasColumn('display_order')) {
            $pageVersions->addColumn('display_order', 'integer', ['default' => 0])->update();
        }

        $this->execute(
            'UPDATE pages
             SET display_order = id
             WHERE display_order = 0'
        );
        $this->execute(
            'UPDATE page_versions v
             SET display_order = p.display_order
             FROM pages p
             WHERE p.id = v.page_id'
        );
    }

    public function down(): void
    {
        $pageVersions = $this->table('page_versions');
        if ($pageVersions->hasColumn('display_order')) {
            $pageVersions->removeColumn('display_order')->update();
        }

        $pages = $this->table('pages');
        if ($pages->hasColumn('display_order')) {
            $pages->removeColumn('display_order')->update();
        }
    }
}
