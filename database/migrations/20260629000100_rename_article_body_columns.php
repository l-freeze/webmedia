<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RenameArticleBodyColumns extends AbstractMigration
{
    public function up(): void
    {
        $articles = $this->table('articles');
        if ($articles->hasColumn('body_markdown') && !$articles->hasColumn('body')) {
            $articles->renameColumn('body_markdown', 'body')->update();
        }
        if ($articles->hasColumn('body_html') && !$articles->hasColumn('rendered_body_html')) {
            $articles->renameColumn('body_html', 'rendered_body_html')->update();
        }

        $articleVersions = $this->table('article_versions');
        if ($articleVersions->hasColumn('body_markdown') && !$articleVersions->hasColumn('body')) {
            $articleVersions->renameColumn('body_markdown', 'body')->update();
        }
    }

    public function down(): void
    {
        $articleVersions = $this->table('article_versions');
        if ($articleVersions->hasColumn('body') && !$articleVersions->hasColumn('body_markdown')) {
            $articleVersions->renameColumn('body', 'body_markdown')->update();
        }

        $articles = $this->table('articles');
        if ($articles->hasColumn('rendered_body_html') && !$articles->hasColumn('body_html')) {
            $articles->renameColumn('rendered_body_html', 'body_html')->update();
        }
        if ($articles->hasColumn('body') && !$articles->hasColumn('body_markdown')) {
            $articles->renameColumn('body', 'body_markdown')->update();
        }
    }
}
