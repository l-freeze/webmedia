# Logging

## 目的

`apps/shared/src/Logging` は CMS/Front 共通で使う JSON Lines 出力の低レベル実装を置く場所です。

Framework は `Psr\Log\LoggerInterface` へ依存するだけにし、具体的な出力形式や保存先は shared 側で決めます。

## ログ種別

ログは目的ごとに分けます。

- ApplicationLog: 障害調査、処理失敗、運用上の状態確認。PSR-3 `LoggerInterface` としても使う。
- Audit: 誰がいつ何を変更したかの業務証跡。`AuditEvent` を保存する。
- SecurityLog: ログイン成功/失敗、認可拒否、CSRF 失敗などの security / auth event を保存する。

標準保存先は次の通りです。

```text
storage/logs/application.log
storage/logs/audit.log
storage/logs/security.log
```

## JsonFileLogger / JsonLineFileWriter

`JsonFileLogger` は 1 行 1 JSON の形式でログを書き込みます。

```json
{"timestamp":"2026-06-01T00:00:00+00:00","severity":"INFO","message":"saved","context":{"articleId":1}}
```

例外を `context['exception']` などに渡した場合は、class、message、file、line を構造化して出力します。

`JsonLineFileWriter` は Audit / SecurityLog / ApplicationLog の共通書き込み処理です。業務側から直接使わず、各 logger 経由で使います。

## 差し込み方

`apps/shared/config/container.php` で以下を登録します。

```php
ApplicationLoggerInterface::class => JsonFileApplicationLogger(storage/logs/application.log)
LoggerInterface::class => ApplicationLoggerInterface::class と同じ instance
AuditLoggerInterface::class => JsonFileAuditLogger(storage/logs/audit.log)
SecurityLoggerInterface::class => JsonFileSecurityLogger(storage/logs/security.log)
```

CMSだけ別ログにしたい場合は、`apps/cms/config/container.php` で shared container 読み込み後に同じ `LoggerInterface::class` を再定義します。

## 方針

Monolog などの汎用実装は使わず、プロジェクトで必要な形式を小さく明示的に実装します。ログ利用側は PSR-3 に依存し、ログ出力側だけを差し替えます。
