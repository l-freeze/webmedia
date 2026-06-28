<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBodyHtmlToArticles extends AbstractMigration
{
    public function up(): void
    {
        $articles = $this->table('articles');
        if (!$articles->hasColumn('body_html')) {
            $articles->addColumn('body_html', 'text', ['null' => true])->update();
        }
    }

    public function down(): void
    {
        $articles = $this->table('articles');
        if ($articles->hasColumn('body_html')) {
            $articles->removeColumn('body_html')->update();
        }
    }
}
