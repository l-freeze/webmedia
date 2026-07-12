<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('email', 'string', ['limit' => 190])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('role', 'string', ['limit' => 30, 'default' => 'admin'])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['email'], ['unique' => true])
            ->create();

        $this->table('categories')
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('slug', 'string', ['limit' => 160])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('seo_description', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        $this->table('tags')
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('slug', 'string', ['limit' => 160])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('seo_description', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        $this->table('articles')
            ->addColumn('title', 'string', ['limit' => 190])
            ->addColumn('slug', 'string', ['limit' => 190])
            ->addColumn('body', 'text')
            ->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])
            ->addColumn('rendered_body_html', 'text', ['null' => true])
            ->addColumn('search_body_text', 'text', ['null' => true])
            ->addColumn('excerpt', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'draft'])
            ->addColumn('current_version_no', 'integer', ['default' => 1])
            ->addColumn('category_id', 'integer', ['null' => true])
            ->addColumn('eyecatch_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('published_at', 'datetime', ['null' => true])
            ->addColumn('published_ends_at', 'datetime', ['null' => true])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('meta_description', 'text', ['null' => true])
            ->addColumn('noindex', 'boolean', ['default' => false])
            ->addColumn('author_name', 'string', ['limit' => 120, 'default' => '編集部'])
            ->addColumn('author_id', 'integer', ['null' => true])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['category_id'])
            ->addIndex(['author_id'])
            ->create();

        $this->table('article_tags', ['id' => false, 'primary_key' => ['article_id', 'tag_id']])
            ->addColumn('article_id', 'integer')
            ->addColumn('tag_id', 'integer')
            ->create();

        $this->table('article_versions')
            ->addColumn('article_id', 'integer')
            ->addColumn('version_no', 'integer')
            ->addColumn('title', 'string', ['limit' => 190])
            ->addColumn('slug', 'string', ['limit' => 190])
            ->addColumn('body', 'text')
            ->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])
            ->addColumn('excerpt', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 30])
            ->addColumn('category_id', 'integer', ['null' => true])
            ->addColumn('eyecatch_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('published_at', 'datetime', ['null' => true])
            ->addColumn('published_ends_at', 'datetime', ['null' => true])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('meta_description', 'text', ['null' => true])
            ->addColumn('note', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('created_by', 'string', ['limit' => 120])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['article_id', 'version_no'], ['unique' => true])
            ->create();

        $this->table('pages')
            ->addColumn('title', 'string', ['limit' => 190])
            ->addColumn('slug', 'string', ['limit' => 190])
            ->addColumn('body', 'text')
            ->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])
            ->addColumn('rendered_body_html', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'draft'])
            ->addColumn('current_version_no', 'integer', ['default' => 1])
            ->addColumn('display_order', 'integer', ['default' => 0])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('meta_description', 'text', ['null' => true])
            ->addColumn('noindex', 'boolean', ['default' => false])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['slug'], ['unique' => true])
            ->create();

        $this->table('page_versions')
            ->addColumn('page_id', 'integer')
            ->addColumn('version_no', 'integer')
            ->addColumn('title', 'string', ['limit' => 190])
            ->addColumn('slug', 'string', ['limit' => 190])
            ->addColumn('body', 'text')
            ->addColumn('body_format', 'string', ['limit' => 20, 'default' => 'markdown'])
            ->addColumn('status', 'string', ['limit' => 30])
            ->addColumn('display_order', 'integer', ['default' => 0])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('meta_description', 'text', ['null' => true])
            ->addColumn('created_by', 'string', ['limit' => 120])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['page_id', 'version_no'], ['unique' => true])
            ->create();

        $this->table('media_files')
            ->addColumn('file_name', 'string', ['limit' => 190])
            ->addColumn('path', 'string', ['limit' => 255])
            ->addColumn('mime_type', 'string', ['limit' => 120])
            ->addColumn('file_size', 'integer')
            ->addColumn('width', 'integer', ['null' => true])
            ->addColumn('height', 'integer', ['null' => true])
            ->addColumn('thumbnail_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('optimized_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('alt_text', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('caption', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('created_at', 'datetime')
            ->create();

        $this->table('inquiries')
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('email', 'string', ['limit' => 190])
            ->addColumn('subject', 'string', ['limit' => 190])
            ->addColumn('message', 'text')
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'new'])
            ->addColumn('admin_note', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->create();

        $this->table('site_settings', ['id' => false])
            ->addColumn('site_name', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('site_description', 'text', ['null' => true])
            ->addColumn('contact_email', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('front_theme', 'string', ['limit' => 80, 'default' => 'cafe-clean', 'null' => false])
            ->addColumn('search_enabled', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('hero_image', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('hero_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('hero_description', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->create();

        $this->table('cms_login_attempts')
            ->addColumn('email_hash', 'string', ['limit' => 64])
            ->addColumn('ip_address', 'string', ['limit' => 45])
            ->addColumn('result', 'string', ['limit' => 20])
            ->addColumn('attempted_at', 'datetime')
            ->addIndex(['email_hash', 'ip_address', 'attempted_at'])
            ->addIndex(['ip_address', 'attempted_at'])
            ->create();

        $this->table('cms_login_attempt_resets')
            ->addColumn('scope', 'string', ['limit' => 20])
            ->addColumn('email_hash', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 45])
            ->addColumn('reset_at', 'datetime')
            ->addColumn('reset_by_user_id', 'integer')
            ->addColumn('reason', 'string', ['limit' => 200, 'default' => ''])
            ->addIndex(['scope', 'email_hash', 'ip_address', 'reset_at'])
            ->addIndex(['scope', 'ip_address', 'reset_at'])
            ->create();

    }
}
