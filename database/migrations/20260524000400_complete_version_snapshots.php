<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CompleteVersionSnapshots extends AbstractMigration
{
    public function up(): void
    {
        $articles = $this->table('articles');
        if (!$articles->hasColumn('current_version_no')) {
            $articles->addColumn('current_version_no', 'integer', ['default' => 1])->update();
        }

        $articleVersions = $this->table('article_versions');
        if (!$articleVersions->hasColumn('slug')) {
            $articleVersions->addColumn('slug', 'string', ['limit' => 190, 'default' => ''])->update();
        }
        if (!$articleVersions->hasColumn('excerpt')) {
            $articleVersions->addColumn('excerpt', 'text', ['null' => true])->update();
        }
        if (!$articleVersions->hasColumn('category_id')) {
            $articleVersions->addColumn('category_id', 'integer', ['null' => true])->update();
        }
        if (!$articleVersions->hasColumn('published_at')) {
            $articleVersions->addColumn('published_at', 'datetime', ['null' => true])->update();
        }
        if (!$articleVersions->hasColumn('published_ends_at')) {
            $articleVersions->addColumn('published_ends_at', 'datetime', ['null' => true])->update();
        }
        if (!$articleVersions->hasColumn('seo_title')) {
            $articleVersions->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])->update();
        }
        if (!$articleVersions->hasColumn('meta_description')) {
            $articleVersions->addColumn('meta_description', 'text', ['null' => true])->update();
        }

        $this->execute(
            'UPDATE article_versions v
             SET slug = a.slug,
                 excerpt = a.excerpt,
                 category_id = a.category_id,
                 published_at = a.published_at,
                 published_ends_at = a.published_ends_at,
                 seo_title = a.seo_title,
                 meta_description = a.meta_description
             FROM articles a
             WHERE a.id = v.article_id'
        );
        $this->execute(
            'INSERT INTO article_versions (
                article_id, version_no, title, slug, body_markdown, excerpt, status, category_id,
                published_at, published_ends_at, seo_title, meta_description, note, created_by, created_at
             )
             SELECT
                a.id, 1, a.title, a.slug, a.body_markdown, a.excerpt, a.status, a.category_id,
                a.published_at, a.published_ends_at, a.seo_title, a.meta_description, \'初期履歴\', \'migration\', a.updated_at
             FROM articles a
             LEFT JOIN article_versions v ON v.article_id = a.id
             WHERE v.article_id IS NULL'
        );
        $this->execute(
            'UPDATE articles a
             SET current_version_no = (
                SELECT COALESCE(MAX(v.version_no), 1)
                FROM article_versions v
                WHERE v.article_id = a.id
             )'
        );

        $pages = $this->table('pages');
        if (!$pages->hasColumn('current_version_no')) {
            $pages->addColumn('current_version_no', 'integer', ['default' => 1])->update();
        }

        $pageVersions = $this->table('page_versions');
        if (!$pageVersions->hasColumn('slug')) {
            $pageVersions->addColumn('slug', 'string', ['limit' => 190, 'default' => ''])->update();
        }
        if (!$pageVersions->hasColumn('seo_title')) {
            $pageVersions->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])->update();
        }
        if (!$pageVersions->hasColumn('meta_description')) {
            $pageVersions->addColumn('meta_description', 'text', ['null' => true])->update();
        }

        $this->execute(
            'UPDATE page_versions v
             SET slug = p.slug,
                 seo_title = p.seo_title,
                 meta_description = p.meta_description
             FROM pages p
             WHERE p.id = v.page_id'
        );
        $this->execute(
            'INSERT INTO page_versions (
                page_id, version_no, title, slug, body_markdown, status, seo_title, meta_description, created_by, created_at
             )
             SELECT
                p.id, 1, p.title, p.slug, p.body_markdown, p.status, p.seo_title, p.meta_description, \'migration\', p.updated_at
             FROM pages p
             LEFT JOIN page_versions v ON v.page_id = p.id
             WHERE v.page_id IS NULL'
        );
        $this->execute(
            'UPDATE pages p
             SET current_version_no = (
                SELECT COALESCE(MAX(v.version_no), 1)
                FROM page_versions v
                WHERE v.page_id = p.id
             )'
        );
    }

    public function down(): void
    {
        $articleVersions = $this->table('article_versions');
        foreach (['meta_description', 'seo_title', 'published_ends_at', 'published_at', 'category_id', 'excerpt', 'slug'] as $column) {
            if ($articleVersions->hasColumn($column)) {
                $articleVersions->removeColumn($column)->update();
            }
        }

        $articles = $this->table('articles');
        if ($articles->hasColumn('current_version_no')) {
            $articles->removeColumn('current_version_no')->update();
        }

        $pageVersions = $this->table('page_versions');
        foreach (['meta_description', 'seo_title', 'slug'] as $column) {
            if ($pageVersions->hasColumn($column)) {
                $pageVersions->removeColumn($column)->update();
            }
        }

        $pages = $this->table('pages');
        if ($pages->hasColumn('current_version_no')) {
            $pages->removeColumn('current_version_no')->update();
        }
    }
}
