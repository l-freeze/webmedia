# WEBメディア 技術スタック要件書

## 1. 目的

この文書は、WEBメディアサイト本体と管理用CMSを開発するための現行技術スタックを明確に定義する。
AIまたは開発者が実装作業を行う際は、この文書を前提として設計・実装すること。

対象は以下である。

- ユーザーフロント：読者が閲覧するWEBメディア本体
- 派生フロント：独立した公開面を提供する追加アプリケーション
- CMS：記事作成・編集・公開予約・更新履歴・バージョン管理を行う管理画面
- `pera` / Kernel / Shared：HTTP実行基盤とプロジェクト共通基盤

---

## 2. 全体方針

### 2.1 基本方針

- Laravelは使用しない
- WordPressは使用しない
- 自前の軽量PHPアプリケーション基盤を作る
- Composerは必ず使用する
- フレームワークは自作するが、必要な部品は信頼できるComposerライブラリを使用する
- フルスタックフレームワークに依存しない
- ユーザーフロント、CMSともにサーバーサイドレンダリングを基本とする
- 必要な箇所のみJavaScriptで非同期化する
- SPAにはしない
- 過剰な抽象化を避け、長期保守しやすい構成にする

### 2.2 技術選定の優先順位

以下を優先する。

1. 保守性
2. 実装の見通しの良さ
3. セキュリティ
4. 表示速度
5. 運用のしやすさ
6. AIによる実装支援のしやすさ
7. 将来の拡張性

---

## 3. 採用技術一覧

| 領域 | 採用技術 |
|---|---|
| 言語 | PHP 8.5 |
| パッケージ管理 | Composer |
| アプリケーション基盤 | 自前軽量フレームワーク `pera` |
| DIコンテナ | 自作PSR-11風DIコンテナ |
| ルーティング | FastRoute |
| テンプレートエンジン | Latte |
| データベース | PostgreSQL |
| DBアクセス | PDO |
| マイグレーション | Phinx |
| JavaScript | Vanilla JavaScript、htmx、Tiptap |
| CSS | Front用CSS、CMSのTailwind CSS 3.4系 |
| Markdown変換 | league/commonmark |
| HTMLサニタイズ | HTMLPurifier |
| メール送信 | Symfony Mailer、通知機能は未実装 |
| 画像処理 | libvips CLI、Framework `ProcessRunner` |
| 環境変数 | vlucas/phpdotenv |
| Webサーバー / PHP実行環境 | FrankenPHP + Caddy |
| 開発環境 | Docker Compose |
| 本番環境 | VPS + Docker Compose |

---

## 4. PHP

### 4.1 採用バージョン

PHP 8.5を採用する。

### 4.2 方針

- 新規開発のため、PHP 8.5を基準にする
- PHP 8.4以下を前提にした実装はしない
- 非推奨機能に依存しない
- 型宣言を積極的に使う
- strict_typesを使用する

### 4.3 実装ルール

PHPファイルには原則として以下を記述する。

```php
<?php

declare(strict_types=1);
```

---

## 5. Composer

Composerを必ず使用する。

### 5.1 方針

- 依存ライブラリはComposerで管理する
- 独自autoloadは作らない
- PSR-4 autoloadを使用する
- vendorディレクトリはGit管理しない

### 5.2 想定ディレクトリ名前空間

```json
{
  "autoload": {
    "psr-4": {
      "App\\Framework\\": "src/Framework/",
      "App\\Kernel\\": "apps/kernel/src/",
      "App\\Shared\\": "apps/shared/src/",
      "App\\Front\\": "apps/front/src/",
      "App\\FrontBusiness\\": "apps/front-business/src/",
      "App\\Cms\\": "apps/cms/src/"
    }
  }
}
```

---

## 6. アプリケーション構成

### 6.1 基本構成

フルスタックフレームワークは使わず、以下のような自前構成にする。

```text
project-root/
├── apps/
│   ├── kernel/
│   ├── shared/
│   ├── front/
│   │   ├── config/
│   │   ├── src/
│   │   │   ├── Application/
│   │   │   ├── Domain/
│   │   │   ├── Infrastructure/
│   │   │   └── Presentation/
│   │   └── templates/
│   ├── front-business/
│   └── cms/
│       ├── config/
│       ├── src/
│       │   ├── Application/
│       │   ├── Domain/
│       │   ├── Infrastructure/
│       │   └── Presentation/
│       └── templates/
├── src/
│   └── Framework/
│       ├── Container/
│       ├── Http/
│       ├── Routing/
│       ├── View/
│       ├── Security/
│       └── Process/
├── public/
│   ├── index.php
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── database/
│   ├── migrations/
│   └── seeds/
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── tests/
├── composer.json
└── docker-compose.yml
```

### 6.2 publicディレクトリ

Web公開ディレクトリは必ず `public/` に限定する。

直接公開してよいものは以下のみ。

- `public/index.php`
- 公開用CSS
- 公開用JavaScript
- 公開用画像
- favicon
- robots.txt
- sitemap.xml

以下はWebから直接アクセスできない場所に置く。

- `src/`
- `apps/*/src/`
- `apps/*/templates/`
- `apps/*/config/`
- `storage/`
- `vendor/`
- `.env`

---

## 7. ルーティング

### 7.1 採用技術

FastRouteを使用する。

### 7.2 方針

- ルート定義はアプリケーションごとの `config/routes.php` に集約する
- Front、派生Front、CMSのルートは明確に分ける
- CMS側のURLには `/admin` を使用する
- ルーティングロジックをコントローラ内に書かない

### 7.3 例

```text
/                       トップページ
/articles/{slug}        記事詳細
/categories/{slug}      カテゴリ詳細
/tags/{slug}            タグ詳細
/search                 検索結果
/contact                問い合わせ

/admin/login            CMSログイン
/admin                  ダッシュボード
/admin/articles         記事一覧
/admin/articles/create  記事作成
/admin/articles/{id}    記事編集
/admin/pages            固定ページ管理
/admin/media            メディア管理
/admin/settings         サイト設定
```

---

## 8. DIコンテナ

### 8.1 方針

DIコンテナは自作する。

### 8.2 必須機能

- クラス名からの自動解決
- interfaceから実装クラスへの解決
- constructor injection
- singleton登録
- factory登録
- 設定値の注入
- 循環依存検出
- `get()` と `has()` を持つPSR-11風インターフェース

### 8.3 実装ルール

- Service Locatorとして濫用しない
- Controller内で直接Containerを使わない
- 依存はconstructorで受け取る
- グローバル変数で依存を解決しない

---

## 9. テンプレートエンジン

### 9.1 採用技術

Latteを採用する。

### 9.2 採用理由

- PHPアプリケーションと相性が良い
- Composerで導入できる
- Laravel非依存
- TwigよりPHP寄りの感覚で書ける
- コンテキスト依存エスケープを備える
- CMSとユーザーフロントの両方に使える

### 9.3 使用方針

- ユーザーフロントとCMSでテンプレートを分ける
- 共通レイアウトを用意する
- 部品テンプレートを積極的に使う
- HTMLのエスケープを徹底する
- テンプレート内に複雑なビジネスロジックを書かない

### 9.4 ディレクトリ例

```text
apps/
├── front/
│   └── templates/
│       ├── layout/
│       ├── article/
│       └── partials/
└── cms/
    └── templates/
        ├── layout/
        ├── article/
        ├── dashboard/
        └── partials/
```

### 9.5 非採用

以下は採用しない。

- Plates
- Laravel Blade
- BladeOne
- 独自テンプレートエンジン

---

## 10. データベース

### 10.1 採用技術

PostgreSQLを使用する。

### 10.2 DBアクセス

PDOを使用する。

### 10.3 方針

- ORMは初期採用しない
- SQLを明示的に管理する
- Domain Entity の永続化は Repository interface / implementation で扱う
- 画面表示や一覧検索の読み取りは Application Query interface / Infrastructure Query implementation で扱う
- プリペアドステートメントを必ず使う
- 生SQLの文字列結合でユーザー入力を埋め込まない

### 10.4 主要テーブル

最低限、以下のテーブルを想定する。

```text
users
articles
article_versions
categories
tags
article_tags
media_files
pages
page_versions
inquiries
site_settings
```

`page_versions` は固定ページの保存履歴を保持する。

```text
page_id       対象固定ページID
version_no    固定ページごとの版番号
title         保存時点のタイトル
slug          保存時点のスラッグ
body         保存時点の本文 source
body_format  保存時点の本文形式
status        保存時点の状態
display_order 保存時点の表示順
seo_title     保存時点のSEOタイトル
meta_description 保存時点のmeta description
created_by    保存者
created_at    保存日時
```

`page_id` と `version_no` の組み合わせは一意である。

`article_versions` は記事編集画面の入力項目を保存時点のスナップショットとして保持する。

```text
article_id     対象記事ID
version_no     記事ごとの版番号
title          保存時点のタイトル
slug           保存時点のスラッグ
body           保存時点の本文 source
body_format    保存時点の本文形式
excerpt        保存時点の抜粋
status         保存時点の表示設定
category_id    保存時点のカテゴリID
published_at   保存時点の公開日時
published_ends_at 保存時点の公開終了日時
seo_title      保存時点のSEOタイトル
meta_description 保存時点のmeta description
note           保存理由
created_by     保存者
created_at     保存日時
```

`articles.current_version_no` と `pages.current_version_no` は現在版番号を保持する。保存時は本体テーブルの更新、履歴テーブルへのスナップショット追加、現在版番号の更新を同一トランザクションで行う。

記事履歴の対象は記事編集画面の入力項目であり、カテゴリーやタグのマスタ定義そのもの、記事とタグの当時の関連付けは復元対象に含めない。

### 10.5 記事ステータス

記事には編集上の表示設定として以下のステータスを持たせる。

```text
draft       下書き
published   表示
private     非表示
trashed     ゴミ箱
```

`公開中`、`公開予約`、`公開終了` はステータス値ではなく、`published` と `published_at` / `published_ends_at` の組み合わせから判定する。

### 10.6 固定ページステータス

固定ページには以下のステータスを持たせる。

```text
draft       下書き
published   公開中
private     非公開
trashed     ゴミ箱
```

公開中の固定ページは、フロント共通レイアウトのナビゲーションリンクとして自動表示する。固定ページ本文やリンク表示のためにHTMLを直接編集してはならない。CMSで固定ページを作成し、状態を `published` にすることで `/pages/{slug}` の表示とフロントのリンク表示対象になる。リンク表示順は `pages.display_order` の昇順、同値の場合は `id` の昇順で決定する。

---

## 11. マイグレーション

### 11.1 採用技術

Phinxを使用する。

### 11.2 方針

- 手動SQLファイルだけでDB変更を管理しない
- DBスキーマ変更はマイグレーションで管理する
- 本番反映時もマイグレーションを使用する
- シードデータは必要最小限にする

---

## 12. JavaScript

### 12.1 採用技術

現行実装では以下を使う。

- Vanilla JavaScript
- htmx
- Tiptap、CMSリッチエディター用

### 12.2 方針

- SPAにはしない
- React、Vue、Svelteは使用しない
- サーバーサイドレンダリングを基本とする
- 必要な部分だけ非同期化する
- 小規模なUI状態は、用途ごとに分離したVanilla JavaScriptで扱う
- HTML差し替えや部分更新が必要な箇所ではhtmxを使ってよい
- ライブラリを読み込んでいることだけを採用理由にせず、実際の用途がある依存だけを維持する

### 12.3 htmxの用途

現在はサーバーサイドレンダリングと通常のform送信を基本とする。画面全体の再描画が操作性や負荷の問題になった場合だけ、対象を限定してhtmxへ移す。

### 12.4 Vanilla JavaScriptの用途

- モーダル
- トースト通知
- 二重送信防止
- 確認ダイアログ
- CMSリッチエディター周辺UI

### 12.5 非採用

以下は採用しない。

- React
- Vue
- Angular
- Svelte
- Next.js
- Nuxt
- フルSPA構成

---

## 13. CSS

### 13.1 採用技術

- 通常Frontと派生Frontは、アプリケーションごとに分離したCSSを使う。
- CMSは現行のTailwind CSS 3.4系ブラウザビルドを使う。

### 13.2 方針

- Front固有CSSをCMSや別Frontへ混ぜない
- CMSはutility classを基本とする
- 必要に応じて独自CSS変数を定義する
- 同じアプリケーション内のコンポーネントは見た目を統一する
- Tailwindのブラウザビルドを本番で継続するか、事前buildへ移すかは公開前に判断する

### 13.3 対象

Tailwind CSSはCMSで使用する。Frontは公開面ごとのCSSを使用する。

### 13.4 非採用

以下は採用しない。

- Bootstrap
- Bulma
- Foundation
- 大規模UIコンポーネントライブラリ

---

## 14. 本文エディタ

### 14.1 方針

CMSの記事・固定ページ本文は、Markdown、HTML、Tiptapリッチエディターから入力形式を選べるようにする。

### 14.2 採用技術

TiptapのOSS editor packageを自前buildして使用する。

Tiptap Cloud、AI Toolkit、Collaboration、Pro Extensionsには依存しない。保存形式と表示用派生HTMLの扱いは `docs/decisions.md` を正とする。

### 14.3 必須機能

- 見出し
- 太字
- リンク
- 箇条書き
- 番号付きリスト
- 引用
- 画像挿入
- 表
- プレビュー

---

## 15. Markdown変換とHTMLサニタイズ

### 15.1 Markdown変換

league/commonmarkを使用する。

### 15.2 HTMLサニタイズ

HTMLPurifierを使用する。

### 15.3 方針

- ユーザー入力や記事本文を無条件にHTML出力しない
- Markdown変換後のHTMLはサニタイズする
- CMS管理者入力であってもXSS対策を行う
- 埋め込みHTMLを許可する場合は許可ルールを明示する

---

## 16. メール送信

### 16.1 採用技術

Symfony Mailerを使用する。

### 16.2 用途

- 問い合わせ通知
- パスワード再設定
- 管理者通知

これらの通知・再設定機能は現時点では未実装であり、運用要件が決まった時点で追加する。Symfony Mailerが依存packageに含まれていることと、メール送信UseCaseが実装済みであることを同一視しない。

### 16.3 方針

- PHPのmail関数を直接使わない
- SMTP設定は環境変数で管理する
- メール本文テンプレートを用意する

---

## 17. 画像処理

### 17.1 採用技術

libvips CLIの `vipsthumbnail` を使用する。PHPからの外部プロセス実行はFrameworkの `ProcessRunner` を通す。

### 17.2 必須処理

- 画像アップロード
- サムネイル生成
- リサイズ
- WebP変換
- 画像メタ情報保存
- altテキスト管理

### 17.3 方針

- 元画像と変換後画像を管理する
- ファイル名は安全な形式に変換する
- アップロード可能な拡張子を制限する
- MIMEタイプを検証する
- JPEG、PNG、WebPはWebPのthumbnail / optimized画像を同期生成する
- 派生生成に失敗しても元画像の登録は成立させる
- PHP画像ライブラリへ変換責務を重複して持たせない

---

## 18. 認証・認可

### 18.1 方針

認証機能は自作する。  
ただし、パスワード処理はPHP標準機能を使用する。

### 18.2 必須機能

- ログイン
- ログアウト
- パスワードハッシュ化
- パスワード検証
- セッション管理
- CSRF対策
- ログイン試行制限

パスワード再設定はメール運用を含む要件が決まった時点で追加する。

### 18.3 パスワード処理

以下を使用する。

- `password_hash()`
- `password_verify()`

ApplicationはPHP標準関数を直接呼ばず、UseCaseが要求するpassword hash / verify Portへ依存する。

### 18.4 権限

初期実装では以下の2種類とする。

```text
admin   全操作可能
editor  記事・固定ページ・メディア・問い合わせ管理が可能
```

---

## 19. セキュリティ

### 19.1 必須対策

- SQLインジェクション対策
- XSS対策
- CSRF対策
- セッション固定攻撃対策
- パスワードハッシュ化
- 管理画面の認証必須化
- ファイルアップロード検証
- 公開ディレクトリ制限
- エラー詳細の本番非表示

### 19.2 CSRF

- CMSのPOST/PUT/PATCH/DELETE相当の操作にはCSRFトークンを必須にする
- htmx経由のリクエストにもCSRFトークンを付与する

### 19.3 セッション

- ログイン成功時にセッションIDを再生成する
- CookieにはHttpOnlyを付ける
- HTTPS環境ではSecureを付ける

無操作時間による再認証は、運用要件が決まった時点で追加する。

---

## 20. インフラ

### 20.1 第一候補

VPS + Docker Compose + FrankenPHP + Caddyを採用する。

### 20.2 方針

- AWS/GCPは個人運用コストが高いため採用しない
- レンタルサーバーは制約が多いため第一候補にしない
- VPSを基本とする
- Docker Composeでアプリ、DB、補助サービスを管理する
- FrankenPHP通常モードを基本とする
- Worker modeは初期必須にしない

### 20.3 代替構成

FrankenPHPで問題が出た場合、以下に切り替えられる構成にする。

```text
第一候補：FrankenPHP + Caddy
第二候補：Caddy + php-fpm
第三候補：nginx + php-fpm
```

### 20.4 重要制約

アプリケーションコードは、FrankenPHP専用実装に依存しすぎないこと。  
Caddy + php-fpm または nginx + php-fpm に移行できる構成を維持する。

---

## 21. Docker Compose

### 21.1 使用方針

開発環境と本番環境の両方でDocker Composeを使用する。

### 21.2 想定サービス

```text
app       PHPアプリケーション / FrankenPHP
db        PostgreSQL
mailpit   開発用メール確認
```

### 21.3 初期不要

以下は初期構成では不要。

- Redis
- Elasticsearch
- Meilisearch
- RabbitMQ
- Node.js常駐サーバー

---

## 22. キャッシュ

### 22.1 初期方針

初期実装ではRedisを採用しない。

### 22.2 使用するキャッシュ

- テンプレートキャッシュ
- 設定キャッシュ
- sitemap生成キャッシュ
- 人気記事などの簡易ファイルキャッシュ

### 22.3 将来候補

アクセス増加後、必要になった場合のみRedisを検討する。

---

## 23. バッチ・定期実行

### 23.1 用途

- 古い一時ファイル削除
- バックアップ実行

### 23.2 方針

- 定期処理が必要になった時点でcronと専用CLI commandを用意する
- Webリクエストに重い定期処理を混ぜない

### 23.3 公開予約

公開予約は保存ステータスではなく、表示対象の記事の `published_at` が未来である状態を指す。

```text
status = published かつ published_at > 現在時刻
```

公開時刻を過ぎた記事はステータス更新を行わず、その時点でフロントの公開対象になる。

この判定は `IClock` の基準時刻をQueryへ渡して行う。公開のためのcron処理は不要である。

---

## 24. ログ

### 24.1 方針

- アプリケーションログを保存する
- Audit logを保存する
- Security logを保存する
- 現行実装では、それぞれ用途別のJSON Linesファイルへ分離して保存する
- Audit管理画面はAudit logファイルをApplication Query経由で読み取る

### 24.2 操作ログ対象

- ログイン
- ログアウト
- 記事作成
- 記事編集
- 記事公開
- 記事公開予約
- 記事非公開
- 記事削除
- 記事復元
- バージョン復元
- バージョンプレビュー
- 固定ページ編集
- メディア削除
- サイト設定変更

---

## 25. テスト

### 25.1 採用技術

PHPUnitを使用する。

### 25.2 方針

最低限、以下をテストする。

- DIコンテナ
- ルーティング
- 認証
- CSRF
- 記事ステータス遷移
- 公開予約処理
- バージョン保存
- バージョン復元
- Repository
- Markdown変換とサニタイズ

---

## 26. フロントビルド

### 26.1 方針

CMSリッチエディターを自前hostするため、最小限のbuild環境を用意する。

### 26.2 採用技術

Viteを使用する。

### 26.3 方針

- React/Vue用の構成にはしない
- 現在はTiptapを使うCMSリッチエディターのJavaScriptだけをbuildする
- build成果物は `public/assets/js/cms-rich-editor.js` に出力する
- Front CSSと共通JavaScriptは現時点ではViteのbuild対象にしない

---

## 27. 非採用技術

以下はこのプロジェクトでは採用しない。

| 技術 | 非採用理由 |
|---|---|
| Laravel | 今回の規模では過剰 |
| WordPress | 自作CMS方針と合わない |
| React | フルSPA不要 |
| Vue | フルSPA不要 |
| Next.js | PHP自前CMS方針と合わない |
| Nuxt | PHP自前CMS方針と合わない |
| Node.jsバックエンド | PHPで統一するため |
| Bootstrap | 現行のCSS方針と合わないため |
| Plates | 採用感・将来性の面で第一候補にしない |
| 独自テンプレートエンジン | 労力に対する利点が薄い |
| 自作ORM | 複雑化しやすいため初期不採用 |
| Redis | 初期規模では不要 |
| Elasticsearch | 初期規模では不要 |
| Meilisearch | 初期規模では不要 |
| AWS | 個人運用コストが高いため不採用 |
| GCP | 個人運用コストが高いため不採用 |

---

## 28. 最終決定スタック

このプロジェクトでは、以下を正式な技術スタックとする。

```text
PHP 8.5
Composer
自前軽量フレームワーク pera
自作PSR-11風DIコンテナ
FastRoute
Latte
PDO
PostgreSQL
Phinx
league/commonmark
HTMLPurifier
Symfony Mailer、送信機能は未実装
libvips CLI + ProcessRunner
vlucas/phpdotenv
Vanilla JavaScript
htmx
Tiptap
Front用CSS
CMS Tailwind CSS 3.4系
Vite
PHPUnit
FrankenPHP + Caddy
Docker Compose
VPS
```

---

## 29. 実装時の厳守事項

AIまたは開発者が実装する際は、以下を必ず守ること。

- Laravelを使わない
- WordPressを使わない
- React/VueのSPAにしない
- Web公開ディレクトリは `public/` のみにする
- Composerを使用する
- PHP 8.5を前提にする
- テンプレートはLatteを使う
- FrontとCMSのCSS責務を混ぜない
- JavaScriptはSSRを補助する範囲に留める
- DBアクセスはPDOを使う
- マイグレーションはPhinxを使う
- インフラはFrankenPHP + Caddyを第一候補にする
- FrankenPHP専用実装に依存しすぎない
- 公開期間判定は `IClock` の基準時刻を使い、公開のためのstatus更新やcronを要求しない
- 記事と固定ページのバージョン管理を実装する
- 記事と固定ページの更新履歴を保存する
- 記事の更新履歴は復元前にプレビューできる
- CSRF、XSS、SQLインジェクション対策を必ず実装する
- 画像アップロードはMIMEタイプと拡張子を検証する
- 管理画面は必ず認証を要求する

---

## 30. 判断が必要な残項目

以下は公開前または運用要件が決まった時点で判断する。

### 30.1 CMS CSS

現行のTailwind CSS 3.4系ブラウザビルドを継続するか、事前buildへ移すかを判断する。

### 30.2 メール送信

問い合わせ通知、パスワード再設定、管理者通知の運用要件を決め、必要なUseCaseだけを実装する。

### 30.3 未使用依存

Composerに残る未使用packageは、実装予定がないことを確認したうえで別の変更単位で削除する。

---

## 31. この文書の位置づけ

この文書は、技術選定の一次資料である。
機能の実装状況は `docs/feature_inventory.md`、設計判断の理由は `docs/decisions.md`、アーキテクチャ境界は `docs/webmedia_architecture_rules.md` を正とする。
