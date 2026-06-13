# Scaffold Commands

## Command Summary

プロジェクト root から次のコマンドを実行します。

| Command | Description |
| --- | --- |
| `php bin/scaffold-kernel` | Framework を使うプロジェクト側に固定の Kernel 層を生成します。通常はプロジェクトごとに一度だけ実行します。 |
| `php bin/scaffold-shared` | CMS / Front / 追加 app から共通利用する `apps/shared` の最小構成を生成します。通常はプロジェクトごとに一度だけ実行します。 |
| `php bin/scaffold-app <name> <path-prefix>` | 新しい HTTP application を `apps/{name}` に生成します。`--template` を省略すると `minimal` template を使います。 |
| `php bin/scaffold-app <name> <path-prefix> --template=bare` | route / Controller / view を含まない config only 構成を生成します。 |
| `php bin/scaffold-app <name> <path-prefix> --template=minimal` | 1つの Controller と view で動く single-controller HTML starter を生成します。 |
| `php bin/scaffold-app <name> <path-prefix> --template=layered` | Application / Domain / Infrastructure を分けた縦割りスターターを生成します。 |
| `php bin/scaffold-app <name> <path-prefix> --template=todo` | PDO repository と Latte view を含む小さな Todo アプリのスターターを生成します。 |

Framework を package として導入した root project では、`php vendor/bin/scaffold-kernel`、`php vendor/bin/scaffold-shared` のように `vendor/bin` から実行します。

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

生成される `apps/kernel/config/application.php` は、先頭で `.env` を任意ロードします。Framework は dotenv を直接読みません。環境変数の読み込み方法は Kernel/project startup の責務です。

## App Scaffold

`bin/scaffold-app` は新しい HTTP application の最小構成を `apps/{name}` に生成します。

```bash
php bin/scaffold-app api /api
```

デフォルト template は `minimal` です。

全 template で生成される基本ファイル:

- `apps/{name}/config/routes.php`
- `apps/{name}/config/container.php`
- `apps/{name}/config/middleware.php`
- `apps/{name}/src/.gitkeep`

`container.php` は shared container を読み込んだうえで、アプリ固有の DI 定義を追加する場所です。

`middleware.php` は shared middleware を読み込んだうえで、アプリ固有の middleware を追加する場所です。

`routes.php` はそのアプリの route 定義を置く場所です。

## Templates

### bare

Kernel に接続するための config only 構成だけを生成します。

```bash
php bin/scaffold-app api /api --template=bare
```

生成物は `config/routes.php`、`config/container.php`、`config/middleware.php`、`src/.gitkeep` だけです。route、Controller、view template、`TemplateRenderer` binding は生成しません。

これまで `--template=minimal` を config only として使っていた自動化は、`--template=bare` へ移行してください。

### minimal

1つの Controller に処理を寄せた、動く最小アプリを生成します。

```bash
php bin/scaffold-app api /api --template=minimal
```

`--template` を省略した場合も `minimal` です。

追加で生成される主な構成:

- `src/Presentation/Http/Controller/Index/Controller.php`: DB 接続、最小データ処理、TemplateRenderer 経由の HTML 出力を同一ソース内で行う Controller
- `templates/index.latte`: 一枚絵の HTML として成立する Latte template

`config/routes.php` には `GET <path-prefix>` の route が追加されます。`path-prefix` が `/` の場合は `/`、それ以外は末尾 slash を除いた prefix を route にします。

`config/container.php` は Kernel container から既存の `PDO::class` binding を読み込み、Shared container を読み込んだうえで、`TemplateRenderer::class` を `LatteTemplateRenderer` へ binding します。scaffold 側で独自の sqlite `PDO::class` binding は生成しません。

`minimal` は layered architecture の見本ではなく、request から HTML response までの導線を最短で確認するための single-controller HTML starter です。層を分けた構成から始める場合は `layered` を使います。

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
- `src/Presentation/Http/Controller`: Controller の例
- `src/Infrastructure/Http/Middleware`: アプリ固有 middleware の例
- `src/Infrastructure/Persistence`: PDO repository 実装の例

`layered` は一通り揃ったスターターです。middleware なし、DB なしのような細かい分類は用意していません。薄く始めたい場合は `minimal` を使います。

### todo

PDO を使う小さな Todo アプリのスターターを生成します。

```bash
php bin/scaffold-app todo /todo --template=todo
```

追加で生成される主な構成:

- `src/Application/Todo`: Todo DTO と repository interface
- `src/Presentation/Http/Controller/Todo`: 一覧表示、追加、完了の Controller
- `src/Infrastructure/Persistence`: PDO repository 実装
- `templates/index.latte`: Todo 一覧の Latte template

`todo` は、Controller に SQL を直接書かずに PDO を使う最小例です。生成された repository はサンプルとして `todos` table を `CREATE TABLE IF NOT EXISTS` で用意します。実運用では migration 管理へ移してください。

### future: fullpack

`fullpack` は、policy、guard、audit、permission、role などの設計が充実してきた段階で追加する全部載せ template の予約名です。現時点では有効な `--template` 値ではありません。

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

`composer require` と同時に Kernel と Shared を生成したい場合は、Framework package 側で Composer plugin を提供し、install event から `scaffold-kernel` と `scaffold-shared` と同等の処理を呼びます。

Framework package の標準 scaffold は Latte 実装を生成するため、package の `require` には `latte/latte` を含めます。ただし Framework core は Latte クラスへ依存せず、`TemplateRenderer` interface だけを持ちます。

plugin を使わない運用では、Framework 追加後に次を実行します。

```bash
php vendor/bin/scaffold-kernel
php vendor/bin/scaffold-shared
```

Framework package 側の Composer plugin は `src/Framework/Installer/Composer/FrameworkInstallerPlugin.php` に置きます。これは Framework installer code で、Framework runtime には含めません。Composer plugin は root project の `allow-plugins` で許可された場合だけ実行されます。plugin は Kernel と Shared の必須 config が存在しない場合だけ scaffold を呼び、既存ファイルは上書きしません。
