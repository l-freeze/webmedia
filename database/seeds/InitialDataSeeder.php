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
                'email' => 'admin@example.com',
                'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                'name' => '管理者',
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();

        $this->table('categories')->insert([
            [
                'id' => 1,
                'name' => 'PHP技術記事',
                'slug' => 'php-tech',
                'description' => 'PHP、設計、運用に関する技術記事。',
                'sort_order' => 1,
                'seo_title' => 'PHP技術記事',
                'seo_description' => 'PHPの実装、設計、運用改善に関する記事一覧。',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => '日常生活の知恵',
                'slug' => 'life-tips',
                'description' => '暮らしの中で使える小さな工夫。',
                'sort_order' => 2,
                'seo_title' => '日常生活の知恵',
                'seo_description' => '毎日の生活を整えるための実用的な知恵。',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();

        $tags = [
            ['id' => 1, 'name' => 'PHP', 'slug' => 'php'],
            ['id' => 2, 'name' => 'PDO', 'slug' => 'pdo'],
            ['id' => 3, 'name' => 'PostgreSQL', 'slug' => 'postgresql'],
            ['id' => 4, 'name' => 'Clean Architecture', 'slug' => 'clean-architecture'],
            ['id' => 5, 'name' => 'テスト', 'slug' => 'testing'],
            ['id' => 6, 'name' => 'Composer', 'slug' => 'composer'],
            ['id' => 7, 'name' => 'セキュリティ', 'slug' => 'security'],
            ['id' => 8, 'name' => 'パフォーマンス', 'slug' => 'performance'],
            ['id' => 9, 'name' => 'CMS', 'slug' => 'cms'],
            ['id' => 10, 'name' => 'SEO', 'slug' => 'seo'],
            ['id' => 11, 'name' => '時短', 'slug' => 'time-saving'],
            ['id' => 12, 'name' => '整理整頓', 'slug' => 'organizing'],
            ['id' => 13, 'name' => '家計管理', 'slug' => 'budgeting'],
            ['id' => 14, 'name' => '健康習慣', 'slug' => 'healthy-habits'],
            ['id' => 15, 'name' => '家事', 'slug' => 'housework'],
        ];

        $this->table('tags')->insert(array_map(
            fn (array $tag): array => $tag + ['created_at' => $now, 'updated_at' => $now],
            $tags,
        ))->saveData();

        $articles = [
            [
                'id' => 1,
                'title' => 'PHP 8.5で型を活かした入力値の受け取り方',
                'slug' => 'php-typed-input-handling',
                'category_id' => 1,
                'excerpt' => 'フォーム入力をValue Objectへ変換し、境界で型を確定させる実装方針を整理します。',
                'body_markdown' => "## 入力値は境界で整える\n\nPHPのWebアプリケーションでは、HTTPリクエストの値をそのままDomainへ渡すと、文字列、空文字、nullの扱いが曖昧になります。\n\nApplicationのInputで受け取り、EntityやValue Objectを生成する時点で意味のある型へ変換すると、後続のRepositoryやQueryが扱う値を安定させられます。\n\n## 実装の要点\n\n- 空文字とnullの扱いを明示する\n- 日時は文字列のまま引き回さず専用Valueで表現する\n- arrayの構造をDomainへ持ち込まない",
                'tags' => [1, 4, 7],
            ],
            [
                'id' => 2,
                'title' => 'PDOでPostgreSQLを扱うときの基本設定',
                'slug' => 'pdo-postgresql-basic-settings',
                'category_id' => 1,
                'excerpt' => 'PDOでPostgreSQLへ接続する際のDSN、例外設定、プリペアドステートメントの注意点をまとめます。',
                'body_markdown' => "## DSNはpgsqlを使う\n\nPostgreSQLへ接続する場合、PDO DSNは `pgsql:host=db;port=5432;dbname=webmedia` のように指定します。\n\n`PDO::ATTR_ERRMODE` は例外にし、SQLエラーを握りつぶさないようにします。保存系の処理ではトランザクションを使い、本体テーブルと履歴テーブルの整合性を守ります。\n\n## 注意点\n\nMySQLの `REPLACE INTO` やダブルクォート文字列は使えません。PostgreSQLでは `ON CONFLICT` とシングルクォート文字列を使います。",
                'tags' => [2, 3, 7],
            ],
            [
                'id' => 3,
                'title' => '記事更新履歴をスナップショットとして保存する設計',
                'slug' => 'article-version-snapshot-design',
                'category_id' => 1,
                'excerpt' => '記事編集画面の入力値を履歴として保存し、復元可能にするテーブル設計を解説します。',
                'body_markdown' => "## 履歴は差分よりスナップショットが扱いやすい\n\nCMSの記事履歴では、タイトル、本文、公開日時、SEO設定などを保存時点の状態として残すと、プレビューと復元が単純になります。\n\n差分管理は容量を抑えられますが、復元時に複数差分を合成する必要があります。小規模メディアでは、まずスナップショット方式が実装と運用のバランスに優れます。\n\n## 現在版番号\n\n本体テーブルに `current_version_no` を持たせることで、現在の版を明示できます。",
                'tags' => [3, 4, 9],
            ],
            [
                'id' => 4,
                'title' => 'Composer autoloadをPSR-4で整理する',
                'slug' => 'composer-psr4-autoload',
                'category_id' => 1,
                'excerpt' => 'apps/front、apps/cms、src/FrameworkをPSR-4で読み込む構成を確認します。',
                'body_markdown' => "## namespaceとディレクトリを合わせる\n\nPSR-4ではnamespaceのprefixと実ディレクトリの対応が重要です。`App\\Cms\\` は `apps/cms/src/`、`App\\Front\\` は `apps/front/src/` に対応させます。\n\nこの対応が崩れると、クラス名からファイルを発見できず、実行時エラーにつながります。\n\n## 分離の利点\n\nfrontとcmsを別アプリケーションとして扱うことで、UseCaseやQueryの責務が読みやすくなります。",
                'tags' => [4, 6, 9],
            ],
            [
                'id' => 5,
                'title' => 'PHPUnitでUseCaseの境界を検証する',
                'slug' => 'phpunit-usecase-boundary',
                'category_id' => 1,
                'excerpt' => 'Interactorの入出力を中心にテストし、Infrastructureに引きずられない確認方法を整理します。',
                'body_markdown' => "## UseCaseは入力と出力で見る\n\nApplicationのInteractorは、Inputを受け取りOutputを返します。この境界をテストすると、ControllerやDBに依存しすぎない検証ができます。\n\nRepositoryやQueryはinterfaceへ依存させ、テストでは必要に応じて差し替えます。\n\n## テストの粒度\n\n細かすぎる実装詳細ではなく、ユースケースとして期待する振る舞いを確認します。",
                'tags' => [4, 5, 6],
            ],
            [
                'id' => 6,
                'title' => 'CMSの公開判定を日時と表示設定から導出する',
                'slug' => 'cms-public-state-derived-from-date',
                'category_id' => 1,
                'excerpt' => '公開中、公開予約、公開終了をステータス値ではなく公開期間から判定する考え方です。',
                'body_markdown' => "## 公開予約を保存ステータスにしない\n\n記事が公開予約かどうかは、表示設定と公開日時の組み合わせで判断できます。`published_at` が未来なら公開予約、`published_ends_at` が過去なら公開終了です。\n\nステータス値に公開予約を持たせると、公開日時との矛盾が起きやすくなります。\n\n## 保存する値\n\n保存するのは表示、非表示、下書き、ゴミ箱などの管理状態と、公開開始・終了日時です。",
                'tags' => [1, 9, 10],
            ],
            [
                'id' => 7,
                'title' => 'SQLインジェクションを避けるPDOの書き方',
                'slug' => 'pdo-sql-injection-prevention',
                'category_id' => 1,
                'excerpt' => 'ユーザー入力をSQL文字列へ直接埋め込まず、プリペアドステートメントで扱う基本を説明します。',
                'body_markdown' => "## 入力値をSQLへ連結しない\n\n検索語やフォーム値をSQL文字列へ直接連結すると、SQLインジェクションの原因になります。\n\nPDOのprepareとexecuteを使い、値はplaceholderとして渡します。LIMITやOFFSETのような整数値も、型を確認したうえでbindします。\n\n## 境界で検証する\n\nControllerで受け取った値はApplication Inputで意味を明確にし、InfrastructureではSQLとして安全に扱います。",
                'tags' => [2, 7, 8],
            ],
            [
                'id' => 8,
                'title' => '朝の10分で一日のタスクを整える方法',
                'slug' => 'morning-ten-minute-task-planning',
                'category_id' => 2,
                'excerpt' => '朝の短い時間で予定、優先順位、持ち物を確認し、迷いを減らす習慣を紹介します。',
                'body_markdown' => "## 最初に今日の予定を見る\n\n朝に予定表を確認し、移動、締切、買い物など時間が決まっているものを先に把握します。\n\n次に、今日必ず終わらせたいことを三つまでに絞ります。数を増やしすぎないことで、着手の迷いが減ります。\n\n## 使う道具\n\n紙のメモでもスマートフォンでも構いません。毎朝同じ場所に書くことが大切です。",
                'tags' => [11, 12, 14],
            ],
            [
                'id' => 9,
                'title' => '冷蔵庫の食材を無駄にしない週末チェック',
                'slug' => 'weekend-fridge-check',
                'category_id' => 2,
                'excerpt' => '週末に冷蔵庫を見直し、買い足しと作り置きを効率よく決める方法です。',
                'body_markdown' => "## 先に期限の近いものを見る\n\n冷蔵庫を開けたら、賞味期限が近いものと開封済みのものを手前に出します。\n\nそれらを中心に献立を考えると、買い物前に使い切る食材が明確になります。\n\n## 買い物メモを作る\n\n足りない食材だけをメモし、重複購入を避けます。調味料もこのタイミングで残量を確認します。",
                'tags' => [11, 13, 15],
            ],
            [
                'id' => 10,
                'title' => '書類を探す時間を減らす家庭内ファイル管理',
                'slug' => 'home-document-filing',
                'category_id' => 2,
                'excerpt' => '保険、学校、税金、家電保証などの書類を迷わず取り出せる分類方法です。',
                'body_markdown' => "## 分類を増やしすぎない\n\n家庭の書類は、保険、税金、学校、家電、住宅のように大きく分けます。細かく分けすぎると戻すのが面倒になります。\n\n## 保管期限を書く\n\n封筒やファイルに保管期限を書いておくと、年末や年度末に整理しやすくなります。",
                'tags' => [12, 13, 11],
            ],
            [
                'id' => 11,
                'title' => '洗濯を夜に回す家庭のための湿気対策',
                'slug' => 'night-laundry-humidity-care',
                'category_id' => 2,
                'excerpt' => '夜の洗濯でも乾きやすく、部屋干し臭を抑えるための置き方と換気の工夫です。',
                'body_markdown' => "## 空気の通り道を作る\n\n洗濯物同士の間隔を空け、厚手のものと薄手のものを交互に干します。扇風機やサーキュレーターは、洗濯物の下から斜めに風を当てると効率的です。\n\n## 換気の時間\n\n短時間でも窓を開け、湿気を外へ逃がします。梅雨時は除湿機を併用すると安定します。",
                'tags' => [15, 14, 11],
            ],
            [
                'id' => 12,
                'title' => '家計簿を続けるために項目を減らす考え方',
                'slug' => 'simple-budget-categories',
                'category_id' => 2,
                'excerpt' => '細かすぎる分類をやめ、固定費、食費、日用品、娯楽費のように続く粒度へ整えます。',
                'body_markdown' => "## 完璧な分類を目指さない\n\n家計簿が続かない理由の一つは、項目が細かすぎることです。まずは大きな分類で支出の傾向を見ます。\n\n## 月に一度だけ見直す\n\n毎日分析する必要はありません。月末に固定費と変動費を確認し、翌月の予算を少し調整します。",
                'tags' => [13, 11, 12],
            ],
            [
                'id' => 13,
                'title' => 'PHPアプリのログを読むときの最初の観点',
                'slug' => 'php-application-log-reading',
                'category_id' => 1,
                'excerpt' => 'エラー発生時にログからリクエスト、例外、SQL、認証状態を順に確認する方法です。',
                'body_markdown' => "## 時刻とリクエストを合わせる\n\n障害調査では、まず発生時刻と対象URLを合わせます。次に例外メッセージ、stack trace、直前のSQLや認証状態を確認します。\n\n## ログに残す情報\n\nユーザー入力の全文や秘密情報は残さず、調査に必要なIDや処理名を記録します。",
                'tags' => [1, 7, 9],
            ],
            [
                'id' => 14,
                'title' => '玄関に置くものを決めて忘れ物を減らす',
                'slug' => 'entryway-forgetfulness-prevention',
                'category_id' => 2,
                'excerpt' => '鍵、財布、交通系カード、郵便物などを玄関で管理し、外出前の確認を簡単にします。',
                'body_markdown' => "## 定位置を一つ決める\n\n鍵や財布は複数の場所に置かず、玄関のトレーや小さな棚に集めます。\n\n## 翌朝使うものを前日に置く\n\n提出物や返却物は夜のうちに玄関へ置くと、朝の判断を減らせます。",
                'tags' => [12, 11, 15],
            ],
            [
                'id' => 15,
                'title' => 'PHPのパフォーマンス改善は計測から始める',
                'slug' => 'php-performance-start-with-measurement',
                'category_id' => 1,
                'excerpt' => '推測で最適化せず、遅い処理、SQL回数、テンプレート描画時間を計測して改善します。',
                'body_markdown' => "## まず遅い場所を特定する\n\nパフォーマンス改善では、体感だけで判断せず、処理時間やSQL回数を記録します。\n\nN+1クエリ、不要なMarkdown変換、過剰な外部通信など、原因を分けて確認します。\n\n## 小さく直す\n\n一度に大きく作り替えず、効果が見える単位で修正して再計測します。",
                'tags' => [1, 5, 8],
            ],
        ];

        $articleRows = [];
        $articleVersionRows = [];
        $articleTagRows = [];
        foreach ($articles as $article) {
            $articleRows[] = [
                'id' => $article['id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'body_markdown' => $article['body_markdown'],
                'excerpt' => $article['excerpt'],
                'status' => 'published',
                'current_version_no' => 1,
                'category_id' => $article['category_id'],
                'published_at' => $now,
                'seo_title' => $article['title'],
                'meta_description' => $article['excerpt'],
                'author_name' => '編集部',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $articleVersionRows[] = [
                'article_id' => $article['id'],
                'version_no' => 1,
                'title' => $article['title'],
                'slug' => $article['slug'],
                'body_markdown' => $article['body_markdown'],
                'excerpt' => $article['excerpt'],
                'status' => 'published',
                'category_id' => $article['category_id'],
                'published_at' => $now,
                'seo_title' => $article['title'],
                'meta_description' => $article['excerpt'],
                'note' => '初期データ',
                'created_by' => '編集部',
                'created_at' => $now,
            ];
            foreach ($article['tags'] as $tagId) {
                $articleTagRows[] = ['article_id' => $article['id'], 'tag_id' => $tagId];
            }
        }

        $this->table('articles')->insert($articleRows)->saveData();
        $this->table('article_versions')->insert($articleVersionRows)->saveData();
        $this->table('article_tags')->insert($articleTagRows)->saveData();

        $this->table('pages')->insert([
            ['id' => 1, 'title' => '運営者情報', 'slug' => 'about', 'body_markdown' => 'このサイトは小規模WEBメディアのデモです。PHP技術記事と日常生活の知恵を中心に掲載します。', 'status' => 'published', 'current_version_no' => 1, 'display_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'プライバシーポリシー', 'slug' => 'privacy', 'body_markdown' => '個人情報は問い合わせ対応の目的で利用します。', 'status' => 'published', 'current_version_no' => 1, 'display_order' => 20, 'created_at' => $now, 'updated_at' => $now],
        ])->saveData();

        $this->table('page_versions')->insert([
            [
                'page_id' => 1,
                'version_no' => 1,
                'title' => '運営者情報',
                'slug' => 'about',
                'body_markdown' => 'このサイトは小規模WEBメディアのデモです。PHP技術記事と日常生活の知恵を中心に掲載します。',
                'status' => 'published',
                'display_order' => 10,
                'created_by' => '編集部',
                'created_at' => $now,
            ],
            [
                'page_id' => 2,
                'version_no' => 1,
                'title' => 'プライバシーポリシー',
                'slug' => 'privacy',
                'body_markdown' => '個人情報は問い合わせ対応の目的で利用します。',
                'status' => 'published',
                'display_order' => 20,
                'created_by' => '編集部',
                'created_at' => $now,
            ],
        ])->saveData();

        $this->table('site_settings')->insert([
            ['setting_key' => 'site_name', 'setting_value' => 'WEB Media'],
            ['setting_key' => 'site_description', 'setting_value' => 'PHP技術記事と日常生活の知恵を届ける小規模WEBメディア'],
            ['setting_key' => 'contact_email', 'setting_value' => 'admin@example.com'],
            ['setting_key' => 'front_theme', 'setting_value' => 'cafe-clean'],
            ['setting_key' => 'search_enabled', 'setting_value' => '1'],
            ['setting_key' => 'hero_title', 'setting_value' => '読みやすく、探しやすいWEBメディア'],
            ['setting_key' => 'hero_description', 'setting_value' => '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。'],
        ])->saveData();

        foreach (['categories', 'tags', 'articles', 'pages'] as $table) {
            $this->execute("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), true)");
        }
    }
}
