<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropScheduledAtFromArticles extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('articles');
        if ($table->hasColumn('scheduled_at')) {
            $table->removeColumn('scheduled_at')->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('articles');
        if (!$table->hasColumn('scheduled_at')) {
            $table->addColumn('scheduled_at', 'datetime', ['null' => true, 'after' => 'published_ends_at'])->update();
        }
    }
}
