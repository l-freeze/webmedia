# Scaffold Commands

## Kernel Scaffold

`bin/scaffold-kernel` は Framework を使うプロジェクト側に固定の Kernel 層を生成します。

```bash
php bin/scaffold-kernel
```

生成されるファイル:

- `apps/kernel/config/application.php`
- `apps/kernel/src/Application.php`
- `apps/kernel/src/ApplicationDefinition.php`
- `apps/kernel/README.md`

Kernel の path と namespace は固定です。

- `apps/kernel`
- `App\Kernel`

namespace を任意指定する option は用意しません。Framework は Kernel に依存しませんが、利用側プロジェクトの合成レイヤーとしてこの形を標準にします。

## App Scaffold

`bin/scaffold-app` は新しい HTTP application の最小構成を `apps/{name}` に生成します。

```bash
php bin/scaffold-app api /api
```

デフォルト template は `minimal` です。

生成されるファイル:

- `apps/{name}/config/routes.php`
- `apps/{name}/config/container.php`
- `apps/{name}/config/middleware.php`
- `apps/{name}/src/.gitkeep`

`container.php` は shared container を読み込んだうえで、アプリ固有の DI 定義を追加する場所です。

`middleware.php` は shared middleware を読み込んだうえで、アプリ固有の middleware を追加する場所です。

`routes.php` はそのアプリの route 定義を置く場所です。

## Templates

### minimal

Kernel に接続するための最小構成だけを生成します。

```bash
php bin/scaffold-app api /api --template=minimal
```

`--template` を省略した場合も `minimal` です。

### layered

CMS と同じ方向性の縦割りスターターを生成します。

```bash
php bin/scaffold-app api /api --template=layered
```

追加で生成される主な構成:

- `src/Application/Health/Show`: Input、Output、Interactor の例
- `src/Domain/Entity`: Entity の例
- `src/Domain/Value`: Value Object の例
- `src/Domain/Repository`: Repository interface の例
- `src/Infrastructure/Http/Controller`: Controller の例
- `src/Infrastructure/Http/Middleware`: アプリ固有 middleware の例
- `src/Infrastructure/Persistence`: PDO repository 実装の例

`layered` は一通り揃ったスターターです。middleware なし、DB なしのような細かい分類は用意していません。薄く始めたい場合は `minimal` を使います。

コマンドは `apps/kernel/config/application.php` や `composer.json` を自動編集しません。生成後に追加する `ApplicationDefinition` と PSR-4 autoload の例を表示します。これは、どの設定がどの役割を持つかを実行者が確認できるようにするためです。

テスト用に別 root へ生成する場合は `--root` を指定できます。

```bash
php bin/scaffold-app api /api --root=/tmp/example-project
```

## Shared Scaffold

`bin/scaffold-shared` は Framework を使うプロジェクト側に `apps/shared` の最小構成を生成します。

```bash
php bin/scaffold-shared
```

生成されるファイル:

- `apps/shared/config/container.php`
- `apps/shared/config/middleware.php`
- `apps/shared/src/Security/Infrastructure/Http/SecurityHeadersMiddleware.php`
- `apps/shared/README.md`

`SecurityHeadersMiddleware` は shared middleware として生成されます。Framework の `Response` はセキュリティヘッダを持たず、アプリケーション側で middleware として差し込みます。

Shared の path と namespace は固定です。

- `apps/shared`
- `App\Shared`

namespace を任意指定する option は用意しません。

### Composer require との関係

通常の dependency scripts は `composer require` した root project では自動実行されません。

`composer require` と同時に `apps/shared` を生成したい場合は、Framework package 側で Composer plugin を提供し、install event からこの shared scaffold と同等の処理を呼びます。

plugin を使わない運用では、Framework 追加後に次を実行します。

```bash
php vendor/bin/scaffold-shared
```
