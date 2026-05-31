<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEyecatchPathToArticleVersions extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('article_versions');
        if (!$table->hasColumn('eyecatch_path')) {
            $table->addColumn('eyecatch_path', 'string', ['limit' => 255, 'null' => true])->update();
        }

        $this->execute(
            'UPDATE article_versions v
                SET eyecatch_path = a.eyecatch_path
               FROM articles a
              WHERE a.id = v.article_id
                AND v.eyecatch_path IS NULL'
        );
    }

    public function down(): void
    {
        $table = $this->table('article_versions');
        if ($table->hasColumn('eyecatch_path')) {
            $table->removeColumn('eyecatch_path')->update();
        }
    }
}
