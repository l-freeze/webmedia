# Bootstrap

## 目的

エントリーポイントで行う Application 生成と Runtime 選択をまとめるための薄い bootstrap 層です。

Framework の外側から `ApplicationBootstrapConfig` を渡すことで、Kernel の生成方法、例外ハンドラ、middleware pipeline、session runtime を差し替えられます。

## 基本形

`public/index.php` では最小構成で起動します。

```php
use Pera\Framework\Bootstrap\ApplicationBootstrap;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$applicationFactory = require $root . '/apps/kernel/config/application.php';

ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(root: $root, applicationFactory: $applicationFactory),
);
```

## 差し替え方

例外ハンドラなどを外側から渡せます。

```php
ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(
        root: dirname(__DIR__),
        applicationFactory: $applicationFactory,
        exceptionHandler: new AppExceptionHandler(),
    ),
);
```

テストや専用 entrypoint では `createApplication()` だけを使い、Runtime 実行を分けることもできます。

```php
$application = ApplicationBootstrap::createApplication(
    config: new ApplicationBootstrapConfig(
        root: $root,
        applicationFactory: $applicationFactory,
    ),
);
```

## 責務

この層は Framework の起動手順だけを扱います。`.env` 読み込み、Route、DI、middleware、Kernel の具体的な中身は各アプリケーション側に置きます。
