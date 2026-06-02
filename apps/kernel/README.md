# Kernel

Kernel はこのプロジェクトの HTTP application を合成する層です。

Framework は `Request`、`Response`、`Router`、`Container`、middleware pipeline、runtime などの部品だけを提供します。`cms`、`front`、将来の `api` や `preview` のようなアプリケーション構成は Kernel 側で定義します。

## アプリケーション定義

`apps/kernel/config/application.php` で `ApplicationDefinition` を追加します。

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

DB エンジンを変える場合でも、Framework は変更しません。Kernel の接続設定、Phinx 設定、必要な Persistence SQL をアプリ側で調整します。

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
