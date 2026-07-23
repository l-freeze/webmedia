# Pera Installation and Scaffolding

この文書は、公開された`pera/framework`と`pera/skeleton`の導入方法、scaffoldの実行方法、利用可能な全templateをまとめた利用者向けガイドです。

Frameworkの設計思想とpublic APIは`README.md`と`API.md`、各scaffoldが生成するfileの詳細は`bin/README.md`を参照してください。

この文書のComposer commandは、Packagistから公開packageを取得する前提です。consumer projectの`composer.json`へ独自のrepository設定を追加する必要はありません。

## Requirements

Framework単体の最低要件は次のとおりです。

- PHP 8.5以上
- Composer 2.2以上
- `ext-session`
- `ext-uri`

選択したscaffoldによって、consumer projectへ追加のruntime依存が入ります。

- Kernel: `vlucas/phpdotenv`
- Shared、`minimal`、`todo`、`layered`: `latte/latte`
- `todo`、`layered`: `ext-pdo`、`ext-pdo_sqlite`

`pera/skeleton`のDocker Compose環境には、初期projectの実行に必要なPHP環境が含まれます。最初の`create-project`にはComposer実行環境が必要ですが、ホストへ直接installせずComposer containerを使うこともできます。project作成後のPHP / Composer操作はSkeleton自身のDocker Compose経由で実行できます。

## Choose an Installation Method

| 目的 | 推奨方法 | 初期状態 |
| --- | --- | --- |
| 新規projectをすぐ起動したい | `composer create-project pera/skeleton` | Kernel、Shared、DBなしMinimal、public entrypoint、Docker Compose |
| 既存projectでFramework APIだけ使いたい | `composer require pera/framework` | Framework libraryとvendor binaryのみ |
| 既存projectへ標準Kernelだけ追加したい | Framework install後に`scaffold-kernel` | 利用者所有のKernelとDotenv |
| Framework、Kernel、plain text Appだけ欲しい | `scaffold-kernel`後に`bare` App | Shared、Latte、DBなし |
| 標準的なHTML projectを組みたい | Kernel、Shared、`minimal`の順にscaffold | Latte、Security Headers、CSRF既定値 |
| TodoまたはDBありlayered sampleが欲しい | Kernel、Shared後に`todo`または`layered` | App所有のSQLite PDO構成 |

`composer require pera/framework`だけでは、Kernel、Shared、App、public entrypointを生成しません。完全な新規projectが必要ならSkeletonを選び、既存projectの構成を自分で管理する場合はFramework packageと明示scaffoldを選びます。

## New Project: Published Skeleton

公開済みpackageから新規projectを作る標準手順です。

```bash
composer create-project pera/skeleton my-app
cd my-app
docker compose up --build
```

ホストへComposerをinstallしない場合は、Composer公式containerでprojectを作成できます。

```bash
docker run --rm --interactive --tty \
  --volume "$PWD":/app \
  composer:2 \
  create-project pera/skeleton /app/my-app

cd my-app
docker compose up --build
```

既定では<http://localhost:8080/>にDBなしMinimal pageが表示されます。8080番portを使えない場合は`APP_PORT`を指定します。

```bash
APP_PORT=8081 docker compose up --build
```

Skeletonには最初から次が含まれます。

```text
apps/kernel
apps/shared
apps/minimal
public/index.php
compose.yaml
.docker/
```

初期Minimalが`/`を使用しています。追加Appには通常`/api`、`/todo`、`/admin`のような別prefixを指定してください。別Appを`/`へ置く場合は、利用者所有の初期Minimal定義とsourceを先に整理する必要があります。

ホストに要件を満たすPHPとComposerがある場合はDockerを使わず起動できます。

```bash
composer install
php -S 127.0.0.1:8080 -t public
```

## Existing Project: Published Framework

既存Composer projectへFramework libraryだけを追加します。

```bash
composer require pera/framework
```

この時点で追加されるのは次だけです。

- `Pera\Framework`のruntime code
- `vendor/bin/scaffold-kernel`
- `vendor/bin/scaffold-shared`
- `vendor/bin/scaffold-app`

既存のproject構造、Composer PSR-4、Kernel、public entrypointは暗黙に変更されません。

### Framework only

FrameworkのHTTP、DI、Routingなどを独自bootstrapへ組み込む場合は、scaffoldを実行する必要はありません。

```bash
composer require pera/framework
```

### Framework and Kernel only

標準Kernelだけを利用者所有sourceとして追加します。

```bash
composer require pera/framework
php vendor/bin/scaffold-kernel
```

scaffoldが最後に表示したComposer commandをproject rootで実行します。通常はDotenvのinstallと`App\Kernel\`のautoload生成が必要です。

Kernel scaffoldはpublic entrypoint、Shared、Appを生成しません。既存projectのentrypointへ組み込むか、完全な初期projectが必要ならSkeletonを使用してください。

### Framework, Kernel, and Bare App

Shared、Latte、DBなしでFrameworkのrequestからresponseまでを確認できます。

```bash
composer require pera/framework
php vendor/bin/scaffold-kernel
# scaffold-kernelが表示したComposer commandを実行

php vendor/bin/scaffold-app api /api --template=bare
# scaffold-appが表示したComposer commandを実行
```

標準Kernelを使う場合、`api`の`ApplicationDefinition`は自動登録されます。

### Standard Kernel, Shared, and HTML App

ブラウザ向けの標準構成です。

```bash
composer require pera/framework

php vendor/bin/scaffold-kernel
# 表示されたComposer commandを実行

php vendor/bin/scaffold-shared
# 表示されたComposer commandを実行

php vendor/bin/scaffold-app site /site --template=minimal
# 表示されたComposer commandを実行
```

`minimal`、`todo`、`layered`はSharedを参照するため、`apps/shared/config/container.php`と`apps/shared/config/middleware.php`が必要です。存在しない場合はproject fileを変更する前に失敗し、先に`scaffold-shared`を実行するよう案内します。

## Scaffold Command Reference

scaffoldは必ずconsumer projectにinstallされた`vendor/bin`経由で実行するのが標準です。

### Kernel

```bash
php vendor/bin/scaffold-kernel
php vendor/bin/scaffold-kernel --root=/absolute/path/to/project
```

固定の生成先とnamespaceは次のとおりです。

```text
apps/kernel
App\Kernel
```

追加される主なComposer設定:

- `vlucas/phpdotenv:^5.6`
- `App\Kernel\ => apps/kernel/src/`

KernelはrequestごとのSession objectを合成しますが、native Session storageはmiddlewareまたはAppが最初にstateへアクセスするまで開始されません。

### Shared

```bash
php vendor/bin/scaffold-shared
php vendor/bin/scaffold-shared --root=/absolute/path/to/project
```

固定の生成先とnamespaceは次のとおりです。

```text
apps/shared
App\Shared
```

追加される主なComposer設定:

- `latte/latte:^3.0`
- `App\Shared\ => apps/shared/src/`

Sharedは次の標準実装を生成します。

- Security Headers middleware
- Framework `CsrfMiddleware`を有効にしたbrowser向けmiddleware既定値
- form validation starter
- Latte renderer
- 未設定のloggingを明示的に失敗させる`PlaceholderLogger` binding

### App

```bash
php vendor/bin/scaffold-app <name> <path-prefix>
php vendor/bin/scaffold-app <name> <path-prefix> --template=bare
php vendor/bin/scaffold-app <name> <path-prefix> --template=minimal
php vendor/bin/scaffold-app <name> <path-prefix> --template=todo
php vendor/bin/scaffold-app <name> <path-prefix> --template=layered
php vendor/bin/scaffold-app <name> <path-prefix> --template=bare --root=/absolute/path/to/project
```

`--template`省略時は`minimal`です。

`name`の規則:

- `/^[a-z][a-z0-9-]*$/`
- 生成先は`apps/{name}`
- `admin-api`のnamespaceは`App\AdminApi`

`path-prefix`の規則:

- `/`から始める
- `/`またはslash区切りのURL path segment
- segmentに利用できる文字は英数字、`.`、`_`、`~`、`-`
- root以外の末尾slashは正規化して除去する
- 既に標準Kernelへ登録済みのApp名またはpath prefixは拒否する

例:

```bash
php vendor/bin/scaffold-app api /api --template=bare
php vendor/bin/scaffold-app account /member/account --template=minimal
php vendor/bin/scaffold-app admin-api /admin-api --template=layered
```

## App Template Matrix

| Template | Shared | Latte | DB | 初期route | 用途 |
| --- | --- | --- | --- | --- | --- |
| `bare` | 不要 | なし | なし | `GET <prefix>` | Frameworkだけのplain text single Controller |
| `minimal` | 必須 | あり | なし | `GET <prefix>` | statelessなsingle Controller HTML page |
| `todo` | 必須 | あり | App所有SQLite | `GET/POST <prefix>`、`POST <prefix>/{id}/complete` | CSRF付きTodoとPDO repositoryの最小例 |
| `layered` | 必須 | あり | App所有SQLite | `GET <prefix>/health`、`GET/POST <prefix>/contact` | Application / Domain / Infrastructureを分けた実行可能例 |

### `bare`

```bash
php vendor/bin/scaffold-app api /api --template=bare
```

- Framework `Response`で`text/plain`を返す
- Shared containerとShared middlewareを読み込まない
- Latte、PDO、Session stateを使わない
- 追加runtime依存はない
- App namespaceのPSR-4は追加する

stateless API、health endpoint、独自architectureの開始点に適しています。

### `minimal`

```bash
php vendor/bin/scaffold-app site /site --template=minimal
```

- single Controller
- `ITemplateRenderer`とSharedのLatte実装
- 一枚の`templates/index.latte`
- DBなし
- SharedのSecurity HeadersとCSRF middlewareを利用
- 初期GETはCSRF stateへ触れないためnative Sessionを開始しない

HTML responseまでの最短導線を確認するtemplateです。layered architectureの見本ではありません。

### `todo`

```bash
php vendor/bin/scaffold-app todo /todo --template=todo
```

- Todo一覧、追加、完了
- CSRF token付きform
- Application側のrepository interface
- Infrastructure側のPDO repository
- App自身の`storage/app.sqlite`
- `CREATE TABLE IF NOT EXISTS`によるsample table作成

SQLiteとtable自動作成はstarterを即時実行可能にするためのものです。実projectではDB選定、connection設定、migration管理を利用者側で置き換えてください。KernelへPDO bindingを要求しません。

### `layered`

```bash
php vendor/bin/scaffold-app admin /admin --template=layered
```

- Application / Domain / Presentation / Infrastructureの分離例
- `GET /admin/health`のDB-backed JSON response
- App固有middlewareによるresponse header
- `GET /admin/contact`のCSRF token付きLatte form
- `POST /admin/contact`のShared Validation利用例
- App自身のSQLite connectionとrepository

`todo`より多くの境界を見せますが、production CMSや利用project固有の高機能architectureは含みません。

### Unsupported and reserved templates

- `fullpack`: 将来予約。現時点では指定できない
- `config-only`: 提供しない
- 完成済みCMSや利用project固有のapplication一式: Framework scaffoldには含めない

## What Scaffold Changes

### Composer manifest

各scaffoldは生成したsourceと必要能力を一致させるため、consumer rootの`composer.json`を更新します。

| Scaffold | runtime `require` | PSR-4 |
| --- | --- | --- |
| Kernel | Dotenv | `App\Kernel\` |
| Shared | Latte | `App\Shared\` |
| App `bare` | 追加なし | 生成App namespace |
| App `minimal` | Latte | 生成App namespace |
| App `todo` | Latte、PDO、SQLite driver | 生成App namespace |
| App `layered` | Latte、PDO、SQLite driver | 生成App namespace |

既存のruntime constraintは上書きしません。必要packageが`require-dev`にだけある場合は、そのconstraintを維持してruntime `require`へ移します。同じPSR-4 namespaceが別pathへ登録済みなら、利用者の設定を上書きせずscaffoldを失敗させます。

### Kernel application registration

標準Kernelの`apps/kernel/config/application.php`には次の管理markerがあります。

```php
// <pera:applications>
// </pera:applications>
```

App scaffoldはこの範囲内だけへ`ApplicationDefinition`を追加します。

- markerがある標準Kernel: 自動登録
- Kernelがない: definitionを表示し、Appは生成
- markerを削除した独自Kernel: Kernelを変更せずdefinitionを表示
- 同じApp名またはpath prefixが登録済み: 生成せず失敗

生成済みKernelは利用者所有です。Framework updateがmarker内外を自動更新することはありません。

### Composer command after scaffold

scaffold自身はdependency解決、download、`composer.lock`、`vendor`更新を行いません。最後に表示されたComposer commandを確認して実行してください。

- runtime依存を追加した: `composer update ...`
- PSR-4だけを追加した: `composer dump-autoload`
- lock fileがまだない: `composer update`

SkeletonのDocker内で実行する場合は、表示されたcommandへDocker Compose prefixを付けます。

```bash
docker compose run --rm app composer dump-autoload
docker compose run --rm app composer update
```

scaffoldがpackage名を限定した`composer update`を表示した場合は、その引数も維持してください。

### Project without `composer.json`

`composer.json`がないrootへの生成自体は許可されます。この場合は必要な`composer require`とPSR-4 entryを表示します。sourceを実行可能にするには、利用者がComposer manifestを作り、表示された設定を追加する必要があります。

## Docker Commands

PHPとComposerを`app` serviceで実行するDocker Compose projectでは、host commandの前へ`docker compose run --rm app`を付けます。

Frameworkだけを導入した既存projectで、Kernel / Sharedがまだ存在しない場合:

```bash
docker compose run --rm app php vendor/bin/scaffold-kernel
docker compose run --rm app php vendor/bin/scaffold-shared
```

通常のSkeletonにはKernelとSharedが最初から存在するため、この2つは再実行しません。Skeletonへ追加Appを生成する全template例:

```bash
docker compose run --rm app php vendor/bin/scaffold-app api /api --template=bare
docker compose run --rm app php vendor/bin/scaffold-app site /site --template=minimal
docker compose run --rm app php vendor/bin/scaffold-app todo /todo --template=todo
docker compose run --rm app php vendor/bin/scaffold-app admin /admin --template=layered
```

scaffold後は表示されたComposer commandを同じcontainer環境で実行します。

```bash
docker compose run --rm app composer dump-autoload
```

または依存追加がある場合:

```bash
docker compose run --rm app composer update
```

## CSRF and Stateless API Composition

Shared scaffoldはbrowser／Session Appに安全な初期値としてCSRFを共通middlewareで有効にします。

- safe methodのGET、HEAD、OPTIONSはCSRF tokenへ触れない
- `minimal`の初期GETはSessionを開始しない
- `todo`と`layered`のform GETはtoken生成時にSessionを開始する
- tokenなしの対象POSTは422になる

後からstateless APIを追加する場合:

1. `apps/shared/config/middleware.php`から`CsrfMiddleware::class`を外す。
2. browser／Sessionを使う各Appの`config/middleware.php`へ`CsrfMiddleware::class`を追加する。
3. Cookieなどambient credentialで認証するAPIには、引き続きCSRF対策を適用する。

最初からSharedを使わないstateless APIなら、`bare`を選べます。

## Ownership, Re-running, and Failure Behavior

- `apps/kernel`、`apps/shared`、`apps/{name}`は生成直後から利用者所有
- Framework updateは生成済みsourceを上書き・migrationしない
- 対象rootが既に存在する場合は、空directoryでも上書きせず失敗
- 生成中は一時rootを使い、完成後にrenameして公開
- 途中失敗時は、生成root、Composer manifest、標準Kernel登録を実行前へ戻す
- 同じscaffoldを既存rootへmergeする用途には使わない

変更したい場合は生成済みsourceを通常のproject codeとして編集します。新しいFramework releaseのtemplateを既存KernelやAppへ自動適用する仕組みではありません。

## Verification after Installation

projectがscriptを提供している場合:

```bash
composer validate --strict
composer test
composer analyse
```

SkeletonのDocker環境:

```bash
docker compose run --rm app composer validate --strict
docker compose run --rm app composer test
docker compose run --rm app composer analyse
```

最低限、選択したtemplateの初期routeを実Kernel経由で確認してください。

| Template | 確認例 |
| --- | --- |
| `bare` | `GET /api`が200とplain textを返す |
| `minimal` | `GET /site`が200とHTMLを返す |
| `todo` | form表示、tokenなしPOSTの422、token付き追加・完了 |
| `layered` | healthの200、contact form、validation、token付きPOST |

## Updating Framework

```bash
composer update pera/framework --with-all-dependencies
```

Framework更新で変わるのはFramework packageです。利用者所有となったKernel、Shared、Appは自動更新されません。生成codeに取り込みたい変更がある場合は、release noteと新templateを確認し、利用project側の変更として判断してください。
