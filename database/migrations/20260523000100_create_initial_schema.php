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
            ->addColumn('body_markdown', 'text')
            ->addColumn('excerpt', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'draft'])
            ->addColumn('category_id', 'integer', ['null' => true])
            ->addColumn('eyecatch_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('published_at', 'datetime', ['null' => true])
            ->addColumn('scheduled_at', 'datetime', ['null' => true])
            ->addColumn('seo_title', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('meta_description', 'text', ['null' => true])
            ->addColumn('noindex', 'boolean', ['default' => false])
            ->addColumn('author_name', 'string', ['limit' => 120, 'default' => '編集部'])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['category_id'])
            ->create();

        $this->table('article_tags', ['id' => false, 'primary_key' => ['article_id', 'tag_id']])
            ->addColumn('article_id', 'integer')
            ->addColumn('tag_id', 'integer')
            ->create();

        $this->table('article_versions')
            ->addColumn('article_id', 'integer')
            ->addColumn('version_no', 'integer')
            ->addColumn('title', 'string', ['limit' => 190])
            ->addColumn('body_markdown', 'text')
            ->addColumn('status', 'string', ['limit' => 30])
            ->addColumn('note', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('created_by', 'string', ['limit' => 120])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['article_id', 'version_no'], ['unique' => true])
            ->create();

        $this->table('pages')
            ->addColumn('title', 'string', ['limit' => 190])
            ->addColumn('slug', 'string', ['limit' => 190])
            ->addColumn('body_markdown', 'text')
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'draft'])
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
            ->addColumn('body_markdown', 'text')
            ->addColumn('status', 'string', ['limit' => 30])
            ->addColumn('created_by', 'string', ['limit' => 120])
            ->addColumn('created_at', 'datetime')
            ->create();

        $this->table('media_files')
            ->addColumn('file_name', 'string', ['limit' => 190])
            ->addColumn('path', 'string', ['limit' => 255])
            ->addColumn('mime_type', 'string', ['limit' => 120])
            ->addColumn('file_size', 'integer')
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

        $this->table('site_settings', ['id' => false, 'primary_key' => ['setting_key']])
            ->addColumn('setting_key', 'string', ['limit' => 120])
            ->addColumn('setting_value', 'text', ['null' => true])
            ->create();

        $this->table('operation_logs')
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('action', 'string', ['limit' => 120])
            ->addColumn('detail', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime')
            ->create();

        $this->table('redirects')
            ->addColumn('from_path', 'string', ['limit' => 255])
            ->addColumn('to_path', 'string', ['limit' => 255])
            ->addColumn('status_code', 'integer', ['default' => 301])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['from_path'], ['unique' => true])
            ->create();
    }
}
