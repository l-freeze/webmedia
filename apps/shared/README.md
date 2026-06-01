# Shared Application Layer

## 目的

`apps/shared` は `cms` と `front` の両方で使うアプリケーション側の共通層です。

Framework に入れるほど汎用ではないが、各アプリへ重複して書きたくない DI 定義、middleware 定義、ログ実装などを置きます。

shared の実装は機能単位で `apps/shared/src/{Feature}` に置きます。機能を削除・差し替え・切り出しやすくするため、技術別の横断ディレクトリをルートには置きません。

```text
apps/shared/src/Cors/Infrastructure/Http/CorsMiddleware.php
apps/shared/src/Audit/Application/AuditLoggerInterface.php
apps/shared/src/Logging/Infrastructure/JsonFileLogger.php
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

## 置いてよいもの

- CMS/Front 共通の DI 定義
- CMS/Front 共通の middleware
- CMS/Front 共通の PSR-3 logger 実装
- アプリ都合の共通 infrastructure

Framework の routing、container、HTTP primitive などは `src/Framework` に置きます。
