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

Kernel はRequestごとに `Session` オブジェクトをContainerへ登録しますが、native PHP SessionはmiddlewareやControllerが最初にsession stateへアクセスした時点で開始します。記事表示、robots、sitemapなどSessionを使わないrequestは、route metadataやpath一覧を追加しなくてもstatelessのままです。問い合わせのCSRF tokenやCMS認証のように `Csrf` / `Session` を使う処理では従来どおり自動的に開始します。

Sessionの開始判断はFrameworkの `Session` 内部へ閉じ、Kernelはrequest scopeの生成と `finally` でのcloseだけを担当します。ControllerやmiddlewareはSession lifecycleを操作せず、従来どおり必要な場合だけ `Session` または `Csrf` を依存として宣言します。

Application codeでは、Sessionを使うControllerのconstructorへ `Pera\Framework\Security\Session` を追加するだけです。注入だけでは開始せず、最初の `get()`、`set()`、flash操作などが開始契機になります。小さなControllerの例、pageや機能がSession keyを所有するclass、CSRF、testの書き方は `packages/framework/src/Security/README.md` を正とします。

Kernel scaffoldはこのlifecycleを最初から組み立てますが、app scaffoldはSession利用Controllerを既定では生成しません。生成直後の公開pageやAPIをstatelessに保ちつつ、必要になったpageではconstructor dependencyを1つ追加するだけで利用できるためです。

`handle()` 内の `loadContainer()` は `require` でコンテナ設定を毎リクエスト再実行します。これは意図的な設計です。コンテナをリクエスト間で再利用すると、`Csrf::class` の singleton が別ユーザーの `Session` を保持し続けるセキュリティリスクがあります。そのリスクはパフォーマンス上の利益を大きく上回るため、コンテナの再利用は行いません。

Worker mode の恩恵であるプロセス起動コストの排除は、`Application` インスタンスの長寿命化だけで十分に得られます。ルート解析とミドルウェア設定の読み込みはコンストラクタで1度だけ実行され、`$routers` と `$middleware` に保持されます。

通常の service 定義は request ごとに作り直されます。DB 接続、Redis client、外部 API client のように worker 単位で再利用したい service だけ、`Container::workerSingleton()` で明示的に登録できます。これは現在の PHP worker のメモリに instance を保持する lifetime 指定です。`Request`、`Session`、`Csrf`、現在ユーザー、flash message など request 固有状態には使いません。

Worker mode 用エントリポイントは `apps/kernel/` に追加します。

## Scaffold

新しいアプリの最小構成は `vendor/bin/scaffold-app` で生成できます。

```bash
php vendor/bin/scaffold-app api /api
```

デフォルトの `minimal` template は、単一 Controller、`GET <path-prefix>` route、Latte template、`ITemplateRenderer` binding を含むDB非依存のsingle-controller HTML starterです。

config only の極薄構成だけが必要な場合は `bare` を使います。

```bash
php vendor/bin/scaffold-app api /api --template=bare
```

このコマンドは `apps/kernel/config/application.php` は自動編集しません。生成後に追加する `ApplicationDefinition` を表示するので、内容を確認して Kernel config へ追加します。

縦割りのスターターも生成できます。

```bash
php vendor/bin/scaffold-app api /api --template=layered
```

`layered` は Controller、Application Interactor、Domain Entity、Value Object、Repository interface、PDO repository、アプリ固有 middleware のサンプルを含みます。薄く始める場合はデフォルトの `minimal` を使います。

PDO を使う小さな Todo スターターも生成できます。

```bash
php vendor/bin/scaffold-app todo /todo --template=todo
```

`todo` は Controller、Latte template、Application-level repository interface、PDO repository を含みます。Controller に SQL を直接書かずに、DB を使う最小の画面として読めるサンプルです。
