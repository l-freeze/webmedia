<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class InitialDataSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->table('users')->insert([
            [
                'email' => getenv('CMS_DEFAULT_EMAIL') ?: 'admin@example.test',
                'password_hash' => password_hash(getenv('CMS_DEFAULT_PASSWORD') ?: 'password', PASSWORD_DEFAULT),
                'name' => '管理者',
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();

        $this->table('categories')->insert([
            ['id' => 1, 'name' => '運営ノート', 'slug' => 'operation', 'description' => '小規模メディア運営の実務メモ。', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => '制作', 'slug' => 'production', 'description' => '記事制作と編集の知見。', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ])->saveData();

        $this->table('tags')->insert([
            ['id' => 1, 'name' => 'CMS', 'slug' => 'cms', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'SEO', 'slug' => 'seo', 'created_at' => $now, 'updated_at' => $now],
        ])->saveData();

        $this->table('articles')->insert([
            [
                'id' => 1,
                'title' => '小さく始めるWEBメディア運営',
                'slug' => 'start-small-webmedia',
                'body_markdown' => "## はじめに\n\n小規模メディアでは、読みやすさと更新しやすさが重要です。\n\n- 記事を探しやすくする\n- 公開事故を防ぐ\n- 履歴を残す",
                'excerpt' => '小規模メディアを継続するための基本方針を整理します。',
                'status' => 'published',
                'category_id' => 1,
                'published_at' => $now,
                'seo_title' => '小さく始めるWEBメディア運営',
                'meta_description' => '小規模メディア運営の基本方針。',
                'author_name' => '編集部',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'title' => '記事編集で確認したいチェックポイント',
                'slug' => 'editorial-checklist',
                'body_markdown' => "## 公開前確認\n\nタイトル、本文、カテゴリ、タグ、SEO設定を確認します。",
                'excerpt' => '公開前に確認したい項目をまとめます。',
                'status' => 'published',
                'category_id' => 2,
                'published_at' => $now,
                'author_name' => '編集部',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();

        $this->table('article_tags')->insert([
            ['article_id' => 1, 'tag_id' => 1],
            ['article_id' => 1, 'tag_id' => 2],
            ['article_id' => 2, 'tag_id' => 1],
        ])->saveData();

        $this->table('pages')->insert([
            ['id' => 1, 'title' => '運営者情報', 'slug' => 'about', 'body_markdown' => 'このサイトは小規模WEBメディアのデモです。', 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'プライバシーポリシー', 'slug' => 'privacy', 'body_markdown' => '個人情報は問い合わせ対応の目的で利用します。', 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
        ])->saveData();

        $this->table('site_settings')->insert([
            ['setting_key' => 'site_name', 'setting_value' => 'WEB Media'],
            ['setting_key' => 'site_description', 'setting_value' => '読みやすく運用しやすい小規模WEBメディア'],
            ['setting_key' => 'contact_email', 'setting_value' => 'admin@example.test'],
        ])->saveData();
    }
}
