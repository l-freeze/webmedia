# Kernel

Kernel はこのプロジェクトの HTTP application を合成する層です。

Framework は `Request`、`Response`、`Router`、`Container`、middleware pipeline、runtime などの部品だけを提供します。`cms`、`front`、将来の `api` や `preview` のようなアプリケーション構成は Kernel 側で定義します。

## アプリケーション定義

`apps/kernel/config/application.php` で `ApplicationDefinition` を追加します。

この config は先頭で `.env` を任意ロードします。Framework は dotenv を直接読みません。環境変数の読み込み方法は Kernel 側の起動構成として扱います。

```php
new ApplicationDefinition(
    name: 'api',
    pathPrefix: '/api',
    routesPath: '/apps/api/config/routes.php',
    containerPath: '/apps/api/config/container.php',
    middlewarePath: '/apps/api/config/middleware.php',
),
```

`pathPrefix` が長い定義から優先して選択されます。たとえば `/api` は `/` より先に判定されます。

各アプリは次の config を持ちます。

- `routes.php`
- `container.php`
- `middleware.php`

## PDO 接続

`apps/kernel/config/container.php` は `PDO::class` を登録します。Framework と Shared は DB エンジンや接続方式を決めません。

`apps/kernel/src/Database/Infrastructure/ConnectionFactory.php` は `DB_DSN` が指定されていればそれを使います。未指定の場合は、従来の PostgreSQL 向け DSN を `DB_HOST`、`DB_PORT`、`DB_DATABASE` から組み立てます。

```text
DB_DSN=mysql:host=127.0.0.1;port=3306;dbname=webmedia;charset=utf8mb4
DB_USERNAME=webmedia
DB_PASSWORD=webmedia
```

DB エンジンを変える場合でも、Framework は変更しません。Kernel の接続設定と、必要な Persistence SQL をアプリ側で調整します。

## Worker Mode

FrankenPHP Worker mode に対応する場合、`Application` インスタンスはプロセス起動時に1度だけ生成し、リクエストごとに `handle()` を呼び出します。

`handle()` 内の `loadContainer()` は `require` でコンテナ設定を毎リクエスト再実行します。これは意図的な設計です。コンテナをリクエスト間で再利用すると、`Csrf::class` の singleton が別ユーザーの `Session` を保持し続けるセキュリティリスクがあります。そのリスクはパフォーマンス上の利益を大きく上回るため、コンテナの再利用は行いません。

Worker mode の恩恵であるプロセス起動コストの排除は、`Application` インスタンスの長寿命化だけで十分に得られます。ルート解析とミドルウェア設定の読み込みはコンストラクタで1度だけ実行され、`$routers` と `$middleware` に保持されます。

Worker mode 用エントリポイントは `apps/kernel/` に追加します。

## Scaffold

新しいアプリの最小構成は `bin/scaffold-app` で生成できます。

```bash
php bin/scaffold-app api /api
```

このコマンドは `apps/{name}` 配下の基本ファイルだけを作り、`apps/kernel/config/application.php` は自動編集しません。生成後に追加する `ApplicationDefinition` を表示するので、内容を確認して Kernel config へ追加します。

縦割りのスターターも生成できます。

```bash
php bin/scaffold-app api /api --template=layered
```

`layered` は Controller、Application Interactor、Domain Entity、Value Object、Repository interface、PDO repository、アプリ固有 middleware のサンプルを含みます。薄く始める場合はデフォルトの `minimal` を使います。

PDO を使う小さな Todo スターターも生成できます。

```bash
php bin/scaffold-app todo /todo --template=todo
```

`todo` は Controller、Latte template、Application-level repository interface、PDO repository を含みます。Controller に SQL を直接書かずに、DB を使う最小の画面として読めるサンプルです。
