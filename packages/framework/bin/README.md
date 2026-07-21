# Scaffold Commands

## Command Summary

プロジェクト root から次のコマンドを実行します。

| Command | Description |
| --- | --- |
| `php vendor/bin/scaffold-kernel` | Framework を使うプロジェクト側に固定の Kernel 層を生成します。通常はプロジェクトごとに一度だけ実行します。 |
| `php vendor/bin/scaffold-shared` | CMS / Front / 追加 app から共通利用する `apps/shared` の最小構成を生成します。通常はプロジェクトごとに一度だけ実行します。 |
| `php vendor/bin/scaffold-app <name> <path-prefix>` | 新しい HTTP application を `apps/{name}` に生成します。`--template` を省略すると `minimal` template を使います。 |
| `php vendor/bin/scaffold-app <name> <path-prefix> --template=bare` | route / Controller / view を含まない config only 構成を生成します。 |
| `php vendor/bin/scaffold-app <name> <path-prefix> --template=minimal` | 1つの Controller と view で動く single-controller HTML starter を生成します。 |
| `php vendor/bin/scaffold-app <name> <path-prefix> --template=layered` | Application / Domain / Infrastructure を分けた縦割りスターターを生成します。 |
| `php vendor/bin/scaffold-app <name> <path-prefix> --template=todo` | PDO repository と Latte view を含む小さな Todo アプリのスターターを生成します。 |

Framework を package として導入した root project では、`php vendor/bin/scaffold-kernel`、`php vendor/bin/scaffold-shared` のように `vendor/bin` から実行します。

Composerが生成したvendor binary proxyから実行した場合は、Composerが渡すroot projectのautoload pathを実在pathへ正規化してから生成先を決めます。package内の実体をproxyなしで直接実行した場合はcurrent working directoryへfallbackします。いずれの場合も`--root=/path/to/project`が明示されていれば、その指定を優先します。

## File Ownership and Atomicity

各scaffoldは`apps/kernel`、`apps/shared`、`apps/{name}`のroot全体を一度だけ生成します。対象rootが既に存在する場合は、空directoryであっても書き込まずに失敗します。既存fileへ部分的に追加するcommandではありません。

生成中は同じ`apps`directory内の一時rootへ全fileを書き、完了後にdirectory renameで対象rootとして公開します。生成または公開に失敗した場合は一時rootを削除し、その実行で変更したconsumer `composer.json`も開始前の内容へ戻します。したがって失敗後は半端な生成物を手で選別せず再実行できます。生成完了後のsourceは利用者所有であり、Framework updateは上書きしません。

## Composer Dependencies

scaffoldが外部packageやPHP extensionを参照するファイルを生成する場合は、その機能に対応する依存レシピをconsumer projectへ適用します。

| Scaffold | consumer rootへ追加するruntime依存 |
| --- | --- |
| `scaffold-kernel` | `vlucas/phpdotenv:^5.6` |
| `scaffold-shared` | `latte/latte:^3.0` |
| `scaffold-app --template=bare` | なし |
| `scaffold-app --template=minimal` | `latte/latte:^3.0` |
| `scaffold-app --template=todo` | `latte/latte:^3.0`、`ext-pdo:*`、`ext-pdo_sqlite:*` |
| `scaffold-app --template=layered` | `ext-pdo:*`、`ext-pdo_sqlite:*` |

root `composer.json`がある場合は、生成機能に合わせて`require`を更新します。既に`require`にある制約は上書きしません。`require-dev`にだけある場合は、その制約を維持したまま`require`へ移します。

scaffold自身は依存解決やdownloadを実行せず、`composer.lock`と`vendor`も変更しません。manifestを更新した場合は、既存の`composer.lock`があれば追加した依存だけに限定した`composer update`、まだlockがなければ初回の`composer update`を表示します。`composer.json`がないrootへの生成は許可し、代わりに必要な`composer require`を表示します。依存を生成しない`bare`はmanifestを読み書きしません。

この依存は生成codeを所有するconsumer projectの依存です。Framework package自体の`require`へLatteを含める理由にはしません。

## Kernel Scaffold

`vendor/bin/scaffold-kernel` は Framework を使うプロジェクト側に固定の Kernel 層を生成します。

```bash
php vendor/bin/scaffold-kernel
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

Dotenvは標準Kernelの起動機能として扱い、Sharedからは`.env`を読み込みません。`scaffold-kernel`は生成sourceに合わせてconsumer rootのruntime依存へ`vlucas/phpdotenv:^5.6`を追加します。

生成されるKernelはRequestごとに遅延 `Session` を登録します。native PHP SessionはmiddlewareやControllerがsession stateへ初めてアクセスしたときだけ開始するため、Sessionを使わないappやrouteの設定は不要です。Kernelはrequest終了時のcloseを常に行い、未開始ならno-opになります。

app scaffoldはSession利用Controllerを既定では生成しません。`minimal`を含む生成直後のGETをstatelessに保ち、公開page、API、Sessionを使うpageのどれにも同じapp scaffoldを使えるようにするためです。Sessionが必要なControllerへ `Pera\Framework\Security\Session` をconstructor injectionし、`get()`、`set()`、flash操作などを呼べば、そのrequestだけnative Sessionが開始します。copy-and-paste可能な最小例と、pageや機能がSession keyを所有する構成は `../src/Security/README.md` にあります。

## App Scaffold

`vendor/bin/scaffold-app` は新しい HTTP application の最小構成を `apps/{name}` に生成します。

```bash
php vendor/bin/scaffold-app api /api
```

デフォルト template は `minimal` です。

正式なsupport対象は`bare`、`minimal`、`todo`、`layered`の全templateです。予約名の`fullpack`は実装されるまで利用できません。

全 template で生成される基本ファイル:

- `apps/{name}/config/routes.php`
- `apps/{name}/config/container.php`
- `apps/{name}/config/middleware.php`
- `apps/{name}/src/.gitkeep`

`container.php` はアプリ固有のDI定義を置く場所です。`minimal`、`todo`、`layered`はShared containerを読み込みますが、`bare`はSharedにも依存しません。

`middleware.php` はアプリ固有middlewareを置く場所です。`minimal`、`todo`、`layered`はShared middlewareを読み込みますが、`bare`は空のmiddleware listから始まります。

`routes.php` はそのアプリの route 定義を置く場所です。

## Templates

### bare

Kernel に接続するための config only 構成だけを生成します。

```bash
php vendor/bin/scaffold-app api /api --template=bare
```

生成物は `config/routes.php`、`config/container.php`、`config/middleware.php`、`src/.gitkeep` だけです。route、Controller、view template、`ITemplateRenderer` bindingは生成せず、Kernel、Shared、DB、template engineにも依存しません。

これまで `--template=minimal` を config only として使っていた自動化は、`--template=bare` へ移行してください。

### minimal

1つの Controller に処理を寄せた、動く最小アプリを生成します。

```bash
php vendor/bin/scaffold-app api /api --template=minimal
```

`--template` を省略した場合も `minimal` です。

追加で生成される主な構成:

- `src/Presentation/Http/Controller/Index/Controller.php`: ITemplateRenderer経由でHTMLを出力するstateless Controller
- `templates/index.latte`: 一枚絵の HTML として成立する Latte template

`config/routes.php` には `GET <path-prefix>` の route が追加されます。`path-prefix` が `/` の場合は `/`、それ以外は末尾 slash を除いた prefix を route にします。

`config/container.php` はShared containerを読み込み、`ITemplateRenderer::class`を`LatteTemplateRenderer`へbindingします。Kernel containerやPDO bindingは読み込みません。

`minimal` は layered architecture の見本ではなく、request から HTML response までの導線を最短で確認するための single-controller HTML starter です。層を分けた構成から始める場合は `layered` を使います。

### layered

CMS と同じ方向性の縦割りスターターを生成します。

```bash
php vendor/bin/scaffold-app api /api --template=layered
```

追加で生成される主な構成:

- `src/Application/Health/Show`: Input、Output、Interactor の例
- `src/Application/Contact/Submit`: Shared Validation を使う入力検証の例
- `src/Domain/Entity`: Entity の例
- `src/Domain/Value`: Value Object の例
- `src/Domain/Repository`: Repository interface の例
- `src/Presentation/Http/Controller`: Controller の例
- `src/Infrastructure/Http/Middleware`: アプリ固有 middleware の例
- `src/Infrastructure/Database`: app所有のSQLite PDO connection factory
- `src/Infrastructure/Persistence`: PDO repository 実装の例

`layered`は一通り揃ったDBありスターターです。SQLite databaseと`PDO::class` bindingはapp自身が所有し、KernelへPDO設定を要求しません。middlewareなし、DBなしのような細かい分類は用意していません。薄く始めたい場合は`minimal`を使います。

### todo

PDO を使う小さな Todo アプリのスターターを生成します。

```bash
php vendor/bin/scaffold-app todo /todo --template=todo
```

追加で生成される主な構成:

- `src/Application/Todo`: Todo DTO と repository interface
- `src/Presentation/Http/Controller/Todo`: 一覧表示、追加、完了の Controller
- `src/Infrastructure/Database`: app所有のSQLite PDO connection factory
- `src/Infrastructure/Persistence`: PDO repository 実装
- `templates/index.latte`: Todo 一覧の Latte template

`todo`は、ControllerにSQLを直接書かずにPDOを使う最小例です。SQLite databaseと`PDO::class` bindingはapp自身が所有し、KernelへPDO設定を要求しません。生成されたrepositoryはサンプルとして`todos` tableを`CREATE TABLE IF NOT EXISTS`で用意します。実運用ではmigration管理へ移してください。

### future: fullpack

`fullpack` は、policy、guard、audit、permission、role などの設計が充実してきた段階で追加する全部載せ template の予約名です。現時点では有効な `--template` 値ではありません。

コマンドは `apps/kernel/config/application.php` やComposerのPSR-4 autoloadを自動編集しません。生成後に追加する `ApplicationDefinition` と PSR-4 autoload の例を表示します。生成物が必要とする外部packageだけは、前述の依存レシピに従ってroot `composer.json`へ反映します。これは、application登録とautoload設定を利用者の合成判断として残しつつ、生成したsourceとpackage依存の不整合を防ぐためです。

テスト用に別 root へ生成する場合は `--root` を指定できます。

```bash
php vendor/bin/scaffold-app api /api --root=/tmp/example-project
```

## Shared Scaffold

`vendor/bin/scaffold-shared` は Framework を使うプロジェクト側に `apps/shared` の最小構成を生成します。

このコマンドは project setup 用です。通常はプロジェクトごとに一度だけ実行します。Controller や app を追加するたびに実行するものではありません。

新規project skeletonではKernelとSharedが揃った状態を標準にします。Framework libraryを既存projectへ追加した場合は自動生成せず、必要な利用者が各scaffoldを明示的に実行します。`scaffold-app` は、その後に個別の app を追加するためのコマンドです。

```bash
php vendor/bin/scaffold-shared
```

生成されるファイル:

- `apps/shared/config/container.php`
- `apps/shared/config/middleware.php`
- `apps/shared/src/Security/Infrastructure/Http/SecurityHeadersMiddleware.php`
- `apps/shared/src/Validation/FormValidator.php`
- `apps/shared/src/Validation/ValidationInput.php`
- `apps/shared/src/Validation/ValidationOutput.php`
- `apps/shared/src/Validation/ValidationError.php`
- `apps/shared/src/View/Infrastructure/LatteTemplateRenderer.php`
- `apps/shared/README.md`

`SecurityHeadersMiddleware` は shared middleware として生成されます。Framework の `Response` はセキュリティヘッダを持たず、アプリケーション側で middleware として差し込みます。

`apps/shared/config/container.php` は、初期状態で `Psr\Log\LoggerInterface` を Framework の `PlaceholderLogger` へ bind します。これは実ロガーではなく、ロガー未設定のまま書き込もうとしたときに例外を投げ、ログが黙って捨てられることを防ぐための仮実装です。アプリケーションで使う Monolog などの PSR-3 実装、またはプロジェクト固有の実装へ差し替えてください。ログを意図的に捨てる場合だけ、`Psr\Log\NullLogger` を明示的に bind します。

`LatteTemplateRenderer` は Framework の `ITemplateRenderer` interface に対する標準実装です。Framework 本体は template engine を決めませんが、scaffold はデフォルトの view 実装として Latte を生成します。

この生成物に合わせ、`scaffold-shared`はconsumer projectのruntime依存へ`latte/latte:^3.0`を追加します。Framework package自体へは追加しません。manifest更新後のinstall手順は「Composer Dependencies」を参照してください。

scaffold が生成する `LatteTemplateRenderer` は、Latte の compiled view cache をデフォルトでは有効にしません。worker mode では一度ロードされた template class が PHP process 内に残るため、compiled view cache は基本不要です。classic / non-worker runtime で使う場合だけ、app container から `cacheDirectory` を明示的に渡してください。

`App\Shared\Validation` はフォーム入力検証の標準スターターです。Framework 本体は Validator を持ちません。必要なら `symfony/validator`、`respect/validation`、`rakit/validation` などの外部ライブラリに置き換えられます。

`layered` template は、インストール時に生成済みの `apps/shared` Validator を使う入力検証例を生成します。

Shared の path と namespace は固定です。

- `apps/shared`
- `App\Shared`

namespace を任意指定する option は用意しません。

### Composer require との関係

Framework library のインストールだけでは Kernel / Shared を生成しません。Kernel を使うかどうかは利用プロジェクトが決め、必要な場合だけ明示的に実行します。

```bash
composer require pera/framework
php vendor/bin/scaffold-kernel
php vendor/bin/scaffold-shared
```

新規プロジェクト向けには、Frameworkと標準starter依存をroot `composer.json` に持つproject skeletonを別途用意する。Framework packageの `require` はFramework runtimeが直接使う依存だけに限定する。

- Framework package: FastRoute、Frameworkが直接実装するPSR interface、vendor binaryが利用するComposer runtime APIなど。
- project skeleton / root project: Dotenv、Latte、利用するPDO driver、migration toolなど。
- 個別app: CommonMark、HTML Purifier、Mailer、画像処理など、そのappだけが必要とするもの。

Framework packageはComposer installer pluginを含まず、install時に利用者のfileを生成しません。将来自動生成が本当に必要になった場合だけ、Framework runtimeから分離したComposer plugin packageとして再設計します。
