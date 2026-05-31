<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBodyFormatToArticles extends AbstractMigration
{
    public function up(): void
    {
        $articles = $this->table('articles');
        if (!$articles->hasColumn('body_format')) {
            $articles->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])->update();
        }

        $articleVersions = $this->table('article_versions');
        if (!$articleVersions->hasColumn('body_format')) {
            $articleVersions->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])->update();
        }

        $this->execute(
            "UPDATE article_versions v
                SET body_format = a.body_format
               FROM articles a
              WHERE a.id = v.article_id
                AND (v.body_format IS NULL OR v.body_format = '')"
        );
    }

    public function down(): void
    {
        $articleVersions = $this->table('article_versions');
        if ($articleVersions->hasColumn('body_format')) {
            $articleVersions->removeColumn('body_format')->update();
        }

        $articles = $this->table('articles');
        if ($articles->hasColumn('body_format')) {
            $articles->removeColumn('body_format')->update();
        }
    }
}
