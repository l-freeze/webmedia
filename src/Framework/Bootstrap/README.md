# Bootstrap

## 目的

エントリーポイントで行う `Application` 生成、`.env` 読み込み、Runtime 選択をまとめるための薄い bootstrap 層です。

Framework の外側から `ApplicationBootstrapConfig` を渡すことで、例外ハンドラ、middleware pipeline、session runtime を差し替えられます。

## 基本形

`public/index.php` では最小構成で起動します。

```php
use App\Framework\Bootstrap\ApplicationBootstrap;
use App\Framework\Bootstrap\ApplicationBootstrapConfig;

require dirname(__DIR__) . '/vendor/autoload.php';

ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(root: dirname(__DIR__)),
);
```

## 差し替え方

例外ハンドラなどを外側から渡せます。

```php
ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(
        root: dirname(__DIR__),
        exceptionHandler: new AppExceptionHandler(),
    ),
);
```

テストや専用 entrypoint では `createApplication()` だけを使い、Runtime 実行を分けることもできます。

```php
$application = ApplicationBootstrap::createApplication(
    config: new ApplicationBootstrapConfig(
        root: $root,
        loadDotenv: false,
    ),
);
```

## 責務

この層は起動手順だけを扱います。Route、DI、middleware、exception handler の具体的な中身は各アプリケーション側に置きます。
