# Shared Application Layer

## 目的

`apps/shared` は `cms` と `front` の両方で使うアプリケーション側の共通層です。

Framework に入れるほど汎用ではないが、各アプリへ重複して書きたくない DI 定義、middleware 定義、ログ実装などを置きます。

shared の実装は機能単位で `apps/shared/src/{Feature}` に置きます。機能を削除・差し替え・切り出しやすくするため、技術別の横断ディレクトリをルートには置きません。

```text
apps/shared/src/Cors/Infrastructure/Http/CorsMiddleware.php
apps/shared/src/ApplicationLog/Application/ApplicationLoggerInterface.php
apps/shared/src/Audit/Application/AuditLoggerInterface.php
apps/shared/src/Cache/Infrastructure/FileSimpleCache.php
apps/shared/src/Database/PdoHelper.php
apps/shared/src/Logging/Infrastructure/JsonFileLogger.php
apps/shared/src/Markdown/Infrastructure/Markdown.php
apps/shared/src/Security/Infrastructure/Http/SecurityHeadersMiddleware.php
apps/shared/src/SecurityLog/Application/SecurityLoggerInterface.php
apps/shared/src/Validation/FormValidator.php
apps/shared/src/View/Infrastructure/LatteTemplateRenderer.php
```

## 依存ルール

`shared` は `Framework` に依存できます。`cms` / `front` へは依存しません。

```text
cms   -> shared -> framework
front -> shared -> framework
```

禁止:

```php
use App\Cms\...;
use App\Front\...;
```

## Container 定義

`apps/shared/config/container.php` は callable を返します。

```php
return static function (Container $container, string $root): void {
    $container->singleton(...);
};
```

`apps/cms/config/container.php` と `apps/front/config/container.php` は、この callable を読み込んでから各アプリ固有の定義を追加します。

## Middleware 定義

`apps/shared/config/middleware.php` は両アプリへ共通適用したい middleware class-string の配列を返します。

CMS/Front 固有の middleware は、それぞれの `apps/*/config/middleware.php` で shared 配列に追加します。

### Security headers middleware

`App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware` は shared middleware として標準適用します。

Framework の `Response` は HTTP response を表現して送信するだけにし、CSP、Referrer-Policy、Permissions-Policy などのアプリケーション要件は shared middleware で差し込みます。

`bin/scaffold-app` が生成する `apps/{name}/config/middleware.php` は shared middleware を読み込むため、新規 app にも自動で適用されます。

### CSRF middleware

`App\Framework\Security\CsrfMiddleware` は shared middleware として標準適用します。

CSRF は session-backed な state-changing request の横断ルールです。Controller ごとの `Csrf::validate()` ではなく middleware で検証します。検証 token は body の `_csrf` だけを読み、query fallback はしません。

`APP_ENV` が `stg`、`staging`、`dev`、`develop`、`development`、`test` の場合は CSRF 検証を無効化します。未指定または production 系では有効です。

除外 path が必要な場合は `CSRF_EXCLUDED_PATHS` に comma 区切りで指定します。完全一致と、末尾 `*` の prefix match を使えます。

```text
APP_ENV=production
CSRF_EXCLUDED_PATHS=/webhook/*,/api/external
```

### CORS middleware

`App\Shared\Cors\Infrastructure\Http\CorsMiddleware` は shared に置きますが、適用するかどうかは各アプリの `config/middleware.php` で決めます。

設定値は各アプリの `config/container.php` で注入します。Front は `FRONT_CORS_*`、CMS は `CMS_CORS_*` の環境変数を使います。

```text
FRONT_CORS_ALLOWED_ORIGINS=https://example.com,https://admin.example.com
FRONT_CORS_ALLOWED_METHODS=GET,POST,OPTIONS
FRONT_CORS_ALLOWED_HEADERS=Content-Type,X-Requested-With
FRONT_CORS_ALLOW_CREDENTIALS=false
FRONT_CORS_MAX_AGE=600
```

許可 origin が空の場合、CORS header は付与しません。

### Logs

`ApplicationLog`、`Audit`、`SecurityLog` は目的を分けます。

- `ApplicationLog` は障害調査や運用状態の確認に使う通常ログです。`Psr\Log\LoggerInterface` も同じ application logger を指します。
- `Audit` は業務証跡です。CMS の状態変更 request や重要 UseCase の業務イベントを `AuditEvent` として記録します。
- `SecurityLog` はログイン成功/失敗、認可拒否、CSRF 失敗などの security / auth event を記録します。

CMS では、GET 以外の状態変更 request はまず横断的に記録対象にします。認証・認可を通過した状態変更は Audit、ログイン/ログアウトや認可拒否は SecurityLog に分けます。

標準保存先は `storage/logs/application.log`、`storage/logs/audit.log`、`storage/logs/security.log` です。

### PSR-16 cache

`apps/shared` は `Psr\SimpleCache\CacheInterface` を共通 cache contract として登録します。

デフォルトは `App\Shared\Cache\Infrastructure\FileSimpleCache` です。保存先は `storage/cache/shared` です。

```php
use Psr\SimpleCache\CacheInterface;

$cache = $container->get(CacheInterface::class);
$cache->set('front.article_list', $articles, 300);
```

cache を無効化したい環境では `SHARED_CACHE_DRIVER=null` を指定します。その場合は `NullCache` が登録され、値は保存されません。

### Latte template renderer

`App\Shared\View\Infrastructure\LatteTemplateRenderer` は `App\Framework\View\TemplateRenderer` の Latte 実装です。

Framework は template engine を決めません。Controller は `TemplateRenderer` に依存し、各アプリの container が Latte または Smarty の具象実装を登録します。独自の template engine への差し替えも容易です。

`LatteTemplateRenderer` は Latte の compiled view cache をデフォルトで無効にします。worker mode では一度ロードされた template class が PHP process 内に残るため、compiled view cache は基本不要です。classic/non-worker runtime で使う場合だけ、app container から `cacheDirectory` を明示的に渡してください。

By default, `LatteTemplateRenderer` disables Latte's compiled view cache. In worker mode, loaded template classes stay in the PHP process, so compiled view cache is usually unnecessary. Pass `cacheDirectory` explicitly only for classic/non-worker runtimes.

#### Smarty に切り替える場合

Latte は標準の starting point ですが、Framework の要件ではありません。

Smarty を使う場合は、次の手順で切り替えます。

1. `smarty/smarty` を project に追加する。
2. `App\Shared\View\Infrastructure\SmartyTemplateRenderer` を作り、`TemplateRenderer` を実装する。
3. 対象 app の container で `TemplateRenderer::class` を `SmartyTemplateRenderer` に bind する。
4. 対象 app の templates を Latte 記法から Smarty 記法へ変換する。

`SmartyTemplateRenderer` には、標準の `LatteTemplateRenderer` と同じ `addGlobalProvider()` を持たせても構いません。site settings、CSRF、flash message、current user などの共通 template 値を container で一度だけ登録でき、Controller ごとに渡す必要がなくなります。

### Validation

`App\Shared\Validation` はフォーム入力検証の標準スターターです。

Framework は Validator を持ちません。入力検証のルール、メッセージ、trim などの正規化はアプリケーション側の要件なので、Shared に置きます。必要なら `symfony/validator`、`respect/validation`、`rakit/validation` などの外部ライブラリへ置き換えます。

### PDO helpers

`App\Shared\Database\PdoHelper` と `App\Shared\Database\PdoRow` は PDO persistence 実装の補助です。

Framework は PDO を要求しません。DB接続方式やDBエンジンは Kernel/App 側の選択です。ただし webmedia の front/cms はPDO persistence実装を共有しているため、補助クラスはSharedに置きます。

## 置いてよいもの

- CMS/Front 共通の DI 定義
- CMS/Front 共通の middleware
- CMS/Front 共通の PSR-3 logger 実装
- CMS/Front 共通の PSR-16 cache 実装
- CMS/Front 共通の template renderer 実装
- CMS/Front 共通の validation starter
- CMS/Front 共通の PDO persistence helper
- アプリ都合の共通 infrastructure

Framework の routing、container、HTTP primitive などは `src/Framework` に置きます。
