# CmsAuditMiddleware

`CmsAuditMiddleware` は CMS の監査対象 request から `AuditEvent` を作り、`AuditLoggerInterface` に渡します。

## 対象

対象は `/admin` 配下の状態変更 method です。

```text
POST
PUT
PATCH
DELETE
```

対象判定は allowlist ではなく denylist で行います。CMS の操作は増えるため、新規操作を監査対象に入れ忘れないことを優先します。監査ログが不要な path だけを denylist に追加します。

初期 denylist は以下です。

```text
/admin/login
/admin/logout
```

GET は初期対象外です。問い合わせ詳細閲覧、エクスポート、ダウンロードなど、GET でも監査が必要な操作が出た場合だけ個別に設計します。

## 登録順

標準順序は次の通りです。

```text
RequireCmsSessionMiddleware
  -> RequireCmsPermissionMiddleware
  -> CmsAuditMiddleware
  -> Controller
```

`CmsAuditMiddleware` は認証・認可を通過した CMS 状態変更 request の HTTP envelope を記録します。

## 例外方針

`CmsAuditMiddleware` は AuditLogger の例外を握りつぶしません。

標準 shared container では `JsonFileAuditLogger` が `storage/logs/audit.log` へ記録します。`PlaceholderAuditLogger` は scaffold やテストで未設定検知を明示したい場合の実装として残します。

実運用の AuditLogger 実装では、HTTP 監査ログの保存失敗だけで完了済み CMS 操作の response を壊さないことを標準にします。保存失敗は通常ログや通知経路へ記録します。

監査ログ保存を業務処理と同じ成否条件にしたい場合は、middleware ではなく対象 UseCase / Controller の Business Audit と永続化処理を同じ transaction 境界で設計します。

## 保存しない情報

middleware は request body 全体、password、CSRF token、cookie、session id を保存しません。

IP address は `Request::$server['REMOTE_ADDR']` を基本にします。trusted proxy 設計を導入するまで、`X-Forwarded-For` などの forwarded header を無条件に信用してはいけません。

DB schema、`AuditEvent` から DB 保存形式への mapping は AuditLogger 実装側の責務です。DB 永続化が必要になったら `PdoAuditLogger` を追加し、`AuditLoggerInterface` の binding を差し替えます。
