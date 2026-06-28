<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSearchBodyTextAndPageRenderedBody extends AbstractMigration
{
    public function up(): void
    {
        $articles = $this->table('articles');
        if (!$articles->hasColumn('search_body_text')) {
            $articles->addColumn('search_body_text', 'text', ['null' => true])->update();
            $this->execute("UPDATE articles SET search_body_text = trim(regexp_replace(COALESCE(rendered_body_html, ''), '<[^>]*>', ' ', 'g'))");
        }

        $pages = $this->table('pages');
        if ($pages->hasColumn('body_markdown') && !$pages->hasColumn('body')) {
            $pages->renameColumn('body_markdown', 'body')->update();
        }
        if (!$pages->hasColumn('body_format')) {
            $pages->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])->update();
        }
        if (!$pages->hasColumn('rendered_body_html')) {
            $pages->addColumn('rendered_body_html', 'text', ['null' => true])->update();
        }

        $pageVersions = $this->table('page_versions');
        if ($pageVersions->hasColumn('body_markdown') && !$pageVersions->hasColumn('body')) {
            $pageVersions->renameColumn('body_markdown', 'body')->update();
        }
        if (!$pageVersions->hasColumn('body_format')) {
            $pageVersions->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])->update();
        }
    }

    public function down(): void
    {
        $pageVersions = $this->table('page_versions');
        if ($pageVersions->hasColumn('body_format')) {
            $pageVersions->removeColumn('body_format')->update();
        }
        if ($pageVersions->hasColumn('body') && !$pageVersions->hasColumn('body_markdown')) {
            $pageVersions->renameColumn('body', 'body_markdown')->update();
        }

        $pages = $this->table('pages');
        if ($pages->hasColumn('rendered_body_html')) {
            $pages->removeColumn('rendered_body_html')->update();
        }
        if ($pages->hasColumn('body_format')) {
            $pages->removeColumn('body_format')->update();
        }
        if ($pages->hasColumn('body') && !$pages->hasColumn('body_markdown')) {
            $pages->renameColumn('body', 'body_markdown')->update();
        }

        $articles = $this->table('articles');
        if ($articles->hasColumn('search_body_text')) {
            $articles->removeColumn('search_body_text')->update();
        }
    }
}
