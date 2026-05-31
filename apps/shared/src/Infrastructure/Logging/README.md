# Logging

## 目的

`apps/shared/src/Infrastructure/Logging` は CMS/Front 共通で使うアプリケーション側の PSR-3 logger 実装を置く場所です。

Framework は `Psr\Log\LoggerInterface` へ依存するだけにし、具体的な出力形式や保存先は shared 側で決めます。

## JsonFileLogger

`JsonFileLogger` は 1 行 1 JSON の形式でログを書き込みます。

```json
{"timestamp":"2026-06-01T00:00:00+00:00","severity":"INFO","message":"saved","context":{"articleId":1}}
```

例外を `context['exception']` などに渡した場合は、class、message、file、line を構造化して出力します。

## 差し込み方

`apps/shared/config/container.php` で `Psr\Log\LoggerInterface` に bind します。

```php
$container->singleton(
    id: LoggerInterface::class,
    factory: fn (): LoggerInterface => new JsonFileLogger($root . '/storage/logs/app.log'),
);
```

CMSだけ別ログにしたい場合は、`apps/cms/config/container.php` で shared container 読み込み後に同じ `LoggerInterface::class` を再定義します。

## 方針

Monolog などの汎用実装は使わず、プロジェクトで必要な形式を小さく明示的に実装します。ログ利用側は PSR-3 に依存し、ログ出力側だけを差し替えます。
