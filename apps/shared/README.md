# Shared Application Layer

## 目的

`apps/shared` は `cms` と `front` の両方で使うアプリケーション側の共通層です。

Framework に入れるほど汎用ではないが、各アプリへ重複して書きたくない DI 定義、middleware 定義、ログ実装などを置きます。

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

## 置いてよいもの

- CMS/Front 共通の DI 定義
- CMS/Front 共通の middleware
- CMS/Front 共通の PSR-3 logger 実装
- アプリ都合の共通 infrastructure

Framework の routing、container、HTTP primitive などは `src/Framework` に置きます。
