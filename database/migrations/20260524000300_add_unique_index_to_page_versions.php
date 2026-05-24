<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueIndexToPageVersions extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('page_versions');
        if (!$table->hasIndex(['page_id', 'version_no'])) {
            $table->addIndex(['page_id', 'version_no'], ['unique' => true])->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('page_versions');
        if ($table->hasIndex(['page_id', 'version_no'])) {
            $table->removeIndex(['page_id', 'version_no'])->update();
        }
    }
}
