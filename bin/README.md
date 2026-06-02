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
- `src/Application/Contact/Submit`: Shared Validation を使う入力検証の例
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

このコマンドは project setup 用です。通常はプロジェクトごとに一度だけ実行します。Controller や app を追加するたびに実行するものではありません。

Framework package としては、インストール時点で Kernel scaffold と Shared scaffold が生成されている状態を標準にします。`scaffold-app` は、その後に個別の app を追加するためのコマンドです。

```bash
php bin/scaffold-shared
```

生成されるファイル:

- `apps/shared/config/container.php`
- `apps/shared/config/middleware.php`
- `apps/shared/src/Database/PdoHelper.php`
- `apps/shared/src/Database/PdoRow.php`
- `apps/shared/src/Security/Infrastructure/Http/SecurityHeadersMiddleware.php`
- `apps/shared/src/Validation/FormValidator.php`
- `apps/shared/src/Validation/ValidationInput.php`
- `apps/shared/src/Validation/ValidationOutput.php`
- `apps/shared/src/Validation/ValidationError.php`
- `apps/shared/src/View/Infrastructure/LatteTemplateRenderer.php`
- `apps/shared/README.md`

`SecurityHeadersMiddleware` は shared middleware として生成されます。Framework の `Response` はセキュリティヘッダを持たず、アプリケーション側で middleware として差し込みます。

`PdoHelper` と `PdoRow` は PDO persistence 実装の標準スターターです。Framework 本体は PDO を要求しません。PDO を使う default persistence examples の補助として Shared に生成します。

`LatteTemplateRenderer` は Framework の `TemplateRenderer` interface に対する標準実装です。Framework 本体は template engine を決めませんが、scaffold はデフォルトの view 実装として Latte を生成します。

`App\Shared\Validation` はフォーム入力検証の標準スターターです。Framework 本体は Validator を持ちません。必要なら `symfony/validator`、`respect/validation`、`rakit/validation` などの外部ライブラリに置き換えられます。

`layered` template は、インストール時に生成済みの `apps/shared` Validator を使う入力検証例を生成します。

Shared の path と namespace は固定です。

- `apps/shared`
- `App\Shared`

namespace を任意指定する option は用意しません。

### Composer require との関係

通常の dependency scripts は `composer require` した root project では自動実行されません。

`composer require` と同時に `apps/shared` を生成したい場合は、Framework package 側で Composer plugin を提供し、install event からこの shared scaffold と同等の処理を呼びます。

Framework package の標準 scaffold は Latte 実装を生成するため、package の `require` には `latte/latte` を含めます。ただし Framework core は Latte クラスへ依存せず、`TemplateRenderer` interface だけを持ちます。

plugin を使わない運用では、Framework 追加後に次を実行します。

```bash
php vendor/bin/scaffold-shared
```

Framework package 化後は、インストール時に Kernel と Shared を生成する Composer plugin を用意する方針です。plugin を使わない運用では、app を追加する前に `scaffold-kernel` と `scaffold-shared` を一度だけ実行します。
