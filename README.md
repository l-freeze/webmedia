# WEB Media

Laravel / WordPress を使わない、小規模WEBメディア + CMS の軽量 PHP アプリケーションです。

## 開発環境

```bash
cp .env.example .env
make up
make install
make migrate
make seed
```

フロント:

- `http://localhost:8080/`

CMS:

- `http://localhost:8080/admin/login`
- 初期メールアドレス: `admin@example.test`
- 初期パスワード: `password`

## Runtime

Docker Compose の開発環境は、[.docker/caddy/Caddyfile](.docker/caddy/Caddyfile) で FrankenPHP worker mode を有効にしています。共通のCaddy設定は1つに保ち、環境差分になるworker watcherだけを次のpresetへ分離しています。

- [worker.dev.caddyfile](.docker/caddy/worker.dev.caddyfile): Docker Composeの既定。`apps`、`config`、Framework source、`public/index.php`を監視する
- [worker.production.caddyfile](.docker/caddy/worker.production.caddyfile): watcherなし。Docker imageの既定

開発presetではPHPやLatte templateを変更するとPHP workerだけが自動再起動するため、通常はコンテナの再起動は不要です。ブラウザを再読み込みすると変更後の画面が表示されます。Caddyfile自体、Composer依存、Docker imageの変更は監視対象外なので、その場合は目的に応じて`docker compose restart app`、Composer更新、image rebuildを行います。

watcherを無効にしてDocker Composeを起動する場合は、production presetを指定してコンテナを再作成します。`restart`だけではbind mount元が切り替わらないため、`--force-recreate`が必要です。

```bash
CADDY_WORKER_CONFIG=production docker compose up -d --force-recreate app
```

開発presetへ戻す場合は次のとおりです。恒常的に切り替える場合は`.env`の`CADDY_WORKER_CONFIG`を`dev`または`production`に変更します。

```bash
CADDY_WORKER_CONFIG=dev docker compose up -d --force-recreate app
```

`worker.production.caddyfile`はwatcherを無効にするworker presetであり、TLS、proxy、secret管理を含む完全な本番デプロイ設定ではありません。本番Caddy全体は配備環境に合わせて別途構成します。

通常の1リクエスト1実行の classic mode で動かす場合は、[.docker/caddy/Caddyfile](.docker/caddy/Caddyfile) の worker mode 用 `handle` block をコメントアウトし、classic mode 用 `handle` block をコメント解除します。

```caddyfile
# handle {
# 	request_header X-Webmedia-Runtime frankenphp-worker
# 	php_server {
# 		worker {
# 			file /app/public/index.php
# 			env APP_RUNTIME frankenphp-worker
# 		}
# 	}
# }

handle {
	request_header X-Webmedia-Runtime classic
	php_server
}
```

`.docker/caddy/Caddyfile` だけの変更で classic mode に切り替えられます。worker block の中で `env APP_RUNTIME frankenphp-worker` を指定しているため、worker block を有効にしたまま `.env` だけで classic mode にすることはできません。

PHP 側の切り替え処理は [packages/framework/src/Runtime/RuntimeFactory.php](packages/framework/src/Runtime/RuntimeFactory.php) にあります。`APP_RUNTIME` 未指定時は `classic` が使われ、`APP_RUNTIME=frankenphp-worker` かつ FrankenPHP worker 関数が利用できる場合だけ worker runtime を使います。

## セッション Cookie

Sessionはrequestごとに注入されますが、native PHP SessionはController、認証、CSRFなどが最初にstateへアクセスしたときだけ開始します。Sessionを使わない公開pageはSession Cookieとbackend lockを持ちません。Controllerでの最短の使い方、pageや機能ごとのkey所有、flash、CSRF、worker lifecycleは [Framework Security README](packages/framework/src/Security/README.md) を参照してください。

Session cookie はアプリケーション側で `HttpOnly` を常に有効にします。`SameSite` と本番での `Secure` 強制は環境変数で指定できます。

```text
SESSION_COOKIE_SAMESITE=Lax
SESSION_COOKIE_SECURE=false
```

`SESSION_COOKIE_SECURE=false` の場合でも、HTTPS リクエストでは `Secure` が付きます。本番で TLS 終端や proxy 構成により HTTPS 判定がずれる場合は、Caddy 側の補強に加えて `SESSION_COOKIE_SECURE=true` を指定します。

## テスト

```bash
make test
```

## Webmedia Skeleton

現在のWebmedia本体を正本として、proprietaryな配布用projectを`packages/webmedia-skeleton`へ同期する。

```bash
make check-webmedia-skeleton
make sync-webmedia-skeleton
```

`check-webmedia-skeleton`は正本との差分だけでなく、`symlink:false`のpath repositoryからまっさらなprojectへ`composer create-project`し、そのproject自身のDocker Composeと空PostgreSQLでComposer検証、PHPUnit、PHPStan、platform requirements、migration、seed、FrankenPHP workerのHTTP受け入れまで実行する。

`sync-webmedia-skeleton`は一時候補へ同じ検証を行い、全て成功した場合だけ`packages/webmedia-skeleton`を置換する。Skeleton側を直接編集せず、変更は正本へ入れてから同期する。`apps/front-business`、monorepo用repository設定、秘密情報、runtime生成物は配布対象に含めない。

## 構成

- `packages/framework`: 独立Composer manifest、scaffold binary、DI、HTTP、Routing、middleware pipeline、runtimeを持つ自己完結したFramework package
- `packages/framework/INSTALLATION.md`: Framework / Skeletonの導入方法、Kernel / Shared / App scaffold、全templateの利用手順
- `packages/skeleton`: `composer create-project pera/skeleton`用のKernel、Shared、DBなしMinimal、FrankenPHP開発環境を持つ自己完結したproject skeleton
- `packages/webmedia-skeleton`: 正本から検証後に同期するproprietaryなFront / CMS project skeleton
- `packages/README.md`: 各packageを公開前にpath repositoryから検証するmonorepo開発手順
- `tools/webmedia-skeleton`: 配布候補の生成、隔離受け入れ、同期を所有するroot開発コマンド
- `apps/kernel`: アプリ構成と環境ごとの合成。PDO 接続設定など、このプロジェクト全体の技術選定を置く
- `apps/shared`: 複数 App で共有する横断関心ごと。セキュリティヘッダ、ログ、キャッシュなど
- `apps/front`: 読者向けフロント。公開済み記事の Query と Presentation/HTTP 表示を担当
- `apps/cms`: 管理画面。認証、記事保存、履歴、管理機能、Presentation/HTTP 表示を担当
- `database/migrations`: Phinx マイグレーション
- `database/seeds`: ローカル確認用シード
- `public`: Web 公開ディレクトリ

## 設計思想

Framework は意図的に小さく保ちます。

Framework は最小の実行基盤だけを提供します。アプリケーション要件は Kernel、Shared、各 App に置きます。機能は、理解しやすく、差し替えやすく、削除しやすい場合にだけ追加します。

賢い抽象より、退屈で明示的で読みやすいコードを優先します。

各 App のディレクトリ責務は App 配下の README にも記載します。Front app は [apps/front/README.md](apps/front/README.md) を参照してください。

## Design Philosophy

The Framework is intentionally kept small.

The Framework provides only the minimum execution foundation. Application concerns belong to Kernel, Shared, or each App. A feature is added only when it keeps the system easier to understand, replace, or delete.

We prefer boring, explicit, readable code over clever abstractions.

## PSR

このプロジェクトは Composer autoload と相性のよい範囲で PSR を採用します。

PSR は標準であること自体を理由に採用しません。軽さと可読性を壊さず、外部との接続価値がある場合に採用します。

- PSR-3: `Psr\Log\LoggerInterface` をログの標準 contract として使います。
- PSR-4: `Pera\Framework`、`App\Kernel`、`App\Shared`、`App\Front`、`App\Cms` を Composer autoload で解決します。
- PSR-11: `Pera\Framework\Container\Container` は `Psr\Container\ContainerInterface` に準拠しています。
- PSR-16: `apps/shared` は `Psr\SimpleCache\CacheInterface` を共通キャッシュ contract として使います。

HTTP の内部モデルは軽量性と可読性を優先し、独自の `Request` / `Response` を使います。`final readonly class` によって不変性を保証しているため、PSR-7 は採用していません。
