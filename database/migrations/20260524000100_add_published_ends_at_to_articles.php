<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPublishedEndsAtToArticles extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('articles');
        if (!$table->hasColumn('published_ends_at')) {
            $table
                ->addColumn('published_ends_at', 'datetime', ['null' => true])
                ->update();
        }

        $this->execute("UPDATE articles SET published_at = scheduled_at, status = 'published' WHERE status = 'scheduled' AND scheduled_at IS NOT NULL");
        $this->execute("UPDATE articles SET status = 'published' WHERE status = 'scheduled'");
    }

    public function down(): void
    {
        $table = $this->table('articles');
        if ($table->hasColumn('published_ends_at')) {
            $table->removeColumn('published_ends_at')->update();
        }
    }
}
