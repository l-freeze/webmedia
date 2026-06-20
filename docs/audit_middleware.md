# Audit Middleware 設計・実装手順

## 目的

CMS の監査対象操作について、「誰が、いつ、何を行い、結果がどうだったか」を証跡として残せるようにする。

この文書は、AI や作業者が現行コードと本書だけを見て、監査ログミドルウェアを設計意図に反せず実装できる粒度で手順を固定する。

## 前提

- このプロジェクトは Web メディア本体であると同時に、軽量 PHP フレームワークの性格を持つ。
- `src/Framework` はアプリケーション都合を持たない。
- `apps/shared` は `cms` / `front` の両方から使うアプリケーション側の共有基盤である。
- `apps/cms` 固有の HTTP middleware は、現時点では `apps/cms/src/Infrastructure/Http/Middleware` に置く。
- `App\Shared\Audit\Application\AuditLoggerInterface` は既に存在する。
- `App\Shared\Audit\Domain\AuditEvent` は既に存在する。
- `App\Shared\Audit\Infrastructure\PlaceholderAuditLogger` は意図的な実装であり、削除しない。

## PlaceholderAuditLogger の扱い

`PlaceholderAuditLogger` は残す。

理由は、フレームワークとして新規アプリケーションを作成した直後は、監査ログの保存先がまだ決まっていない状態が自然だからである。その状態で Audit を使うコードを書いた場合、何も記録せずに処理が成功すると、監査ログ未設定に気付けない。

そのため、初期状態では `AuditLoggerInterface` に `PlaceholderAuditLogger` を bind し、Audit を記録しようとした時点で例外を出す。

方針:

- scaffold や初期 shared container では `PlaceholderAuditLogger` を使ってよい。
- Audit を実際に使うアプリケーションは、明示的に `PdoAuditLogger` などの実装へ差し替える。
- `NullAuditLogger` は標準実装にしない。監査対象操作を沈黙して捨てると設定漏れに気付けないためである。
- 本番で監査ログが必須のアプリケーションでは、`PlaceholderAuditLogger` のまま起動しないように container test または bootstrap check を追加してよい。

## 監査ログと他ログの分類

監査ログ、HTTP アクセスログ、認証ログは同じものではない。

### 監査ログ

CMS の業務データや管理対象に対する操作証跡である。

例:

- `article.created`
- `article.updated`
- `article.deleted`
- `page.updated`
- `media.uploaded`
- `media.deleted`
- `taxonomy.created`
- `taxonomy.updated`
- `setting.updated`

監査ログは「業務上、後から操作を説明できること」を目的にする。

### HTTP アクセスログ

HTTP request / response の記録である。

例:

- method
- path
- status code
- duration
- IP address
- user agent

HTTP アクセスログは、通信実績、性能、障害調査を目的にする。監査ログとは目的が違うため、全 HTTP request を Audit として保存しない。

### 認証・セキュリティログ

ログイン成功、ログイン失敗、ロックアウト、CSRF 失敗、認可拒否などの security event である。

CMS ログインは、業務データの変更ではなく認証イベントである。そのため、初期の Audit middleware の対象にはしない。将来ログイン試行を記録したい場合は、Audit ではなく auth/security log として別途扱う。

## HTTP と操作ログが一対一とは限らない理由

監査対象操作は HTTP request と一対一になるとは限らない。

例:

- `POST /admin/articles/10` が validation error で 422 になった場合、HTTP request は存在するが記事更新は成立していない。
- 1つの記事保存 request で、記事本文の更新、タグ付け替え、履歴作成、公開状態変更が同時に起きることがある。
- 復元操作では、HTTP path だけでは「どの版から復元したか」「復元前後の状態」が分からない。
- 将来 CLI、batch、API から同じ UseCase を実行する場合、HTTP request なしで業務操作が発生する。

したがって、最終的には次の2段階で考える。

- `CmsAuditMiddleware`: CMS の監査対象 HTTP request から、最低限の操作証跡を記録する。
- UseCase / Controller からの明示 Audit: before / after、resource id、業務上の詳細が必要な操作で追加する。

ただし初期実装では、無理に2つの middleware に分けない。まず `CmsAuditMiddleware` ひとつで始める。

## GET を初期対象外にする理由

初期の Audit middleware は GET を記録しない。

理由は、一般に監査ログの主対象は状態変更であり、GET を全件監査ログへ入れると記録量とノイズが増え、業務証跡として読みにくくなるためである。

初期対象 method:

- `POST`
- `PUT`
- `PATCH`
- `DELETE`

GET でも監査対象にしてよいケースはある。

- 個人情報や機密情報の詳細閲覧
- 問い合わせ詳細閲覧
- エクスポート
- ダウンロード
- 管理上のプレビュー閲覧を証跡化したい場合

このプロジェクトの初期実装では GET 全般は対象外にする。GET の監査が必要になった場合は、全 GET を対象にするのではなく、path 単位で明示的に対象へ追加する。

## ミドルウェアを1つにする理由

初期実装では、追加する middleware は `CmsAuditMiddleware` ひとつにする。

理由は、今回の目的が「CMS の監査対象操作を AuditLoggerInterface へ流すこと」に限定されており、request id、access log、auth/security log、current user context などを独立機能としてまだ要求していないためである。

無理に複数 middleware へ分けると、設定順序、責務、テスト対象が増え、現時点の要件に対して複雑になる。

分ける正当な理由が出る例:

- request id を通常ログ、レスポンスヘッダ、監査ログで共通利用したい場合は `RequestIdMiddleware` を分ける。
- HTTP access log を全件取りたい場合は `AccessLogMiddleware` を分ける。
- ログイン成功、ログイン失敗、CSRF 失敗を記録したい場合は auth/security log 用 middleware または handler を分ける。
- 認証済みユーザー情報を Controller / UseCase へ渡したい場合は、Request attributes または CurrentUser 導入を検討する。

これらの必要が具体化するまでは、`CmsAuditMiddleware` ひとつで実装する。

## 実装対象

### 追加する class

```text
apps/cms/src/Infrastructure/Http/Middleware/CmsAuditMiddleware.php
```

### 必要に応じて追加する class

実際に DB 保存するアプリケーションでは、次を追加する。

```text
apps/shared/src/Audit/Infrastructure/Persistence/PdoAuditLogger.php
```

`PdoAuditLogger` は shared に置く。理由は、AuditLogger の保存先実装は CMS 固有の画面・Controller 事情ではなく、`front` や将来 app でも使える共有のアプリケーション基盤だからである。

ただし、scaffold 初期状態の `PlaceholderAuditLogger` は維持する。

### 追加する migration

DB へ保存する場合は `audit_logs` テーブルを追加する。

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    occurred_at DATETIME NOT NULL,
    action VARCHAR(120) NOT NULL,
    resource_type VARCHAR(80) NOT NULL,
    resource_id VARCHAR(80) NULL,
    result VARCHAR(30) NOT NULL,
    actor_user_id VARCHAR(80) NULL,
    actor_role VARCHAR(80) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    request_id VARCHAR(80) NULL,
    http_method VARCHAR(16) NULL,
    path VARCHAR(255) NULL,
    status_code INT NULL,
    before_json JSON NULL,
    after_json JSON NULL,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL
);
```

`http_method`、`path`、`status_code` は middleware 由来の情報を metadata に埋め込まず、検索しやすい列として持つ。

## CmsAuditMiddleware の責務

`CmsAuditMiddleware` は次だけを行う。

- CMS の監査対象 request か判定する。
- 対象外なら何も記録せず次へ進める。
- 対象なら handler 実行後に `AuditEvent` を作る。
- handler が例外を投げた場合は failure として記録を試み、その後に同じ例外を再 throw する。
- `AuditLoggerInterface::record()` を呼ぶ。

`CmsAuditMiddleware` が行わないこと:

- request body 全体を保存しない。
- password、CSRF token、cookie、session id を保存しない。
- before / after を推測しない。
- resource id を無理に path から解析しない。
- ログイン成功・失敗を扱わない。
- HTTP access log の代替にしない。
- AuditLogger の実装を自分で選ばない。DI container の bind に従う。

## 記録する値

middleware で記録する `AuditEvent` は、次の値を持つ。

```text
action: cms.http_state_changed
resourceType: cms_request
resourceId: null
result: success または failure
actorUserId: Session の cms.user_id
actorRole: null
ipAddress: Request server から取得
userAgent: Request server から取得
requestId: server に request id があれば使用。なければ null
before: []
after: []
metadata:
  method
  path
  status_code
```

`action` を path ごとに細かく分けすぎない。middleware は HTTP envelope の証跡を担当し、`article.updated` のような業務イベントは UseCase / Controller 側で明示的に記録する。

## 対象判定

初期対象は CMS の状態変更 request とする。

対象:

```text
POST /admin/articles...
POST /admin/pages...
POST /admin/categories...
POST /admin/tags...
POST /admin/media...
POST /admin/settings...
POST /admin/trash...
PUT /admin/...
PATCH /admin/...
DELETE /admin/...
```

対象外:

```text
GET ...
POST /admin/login
POST /admin/logout
```

`POST /admin/logout` は認証状態の破棄であり、初期 Audit 対象にはしない。必要になった場合は auth/security log 側で扱う。

対象判定は class 内の private method に閉じ込める。

```php
private function shouldAudit(Request $request): bool
```

## 実装例

実装時は現行の `Request` / `Response` / middleware interface に合わせる。

```php
<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Middleware;

use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\Audit\Domain\AuditEvent;
use Throwable;

final readonly class CmsAuditMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuditLoggerInterface $auditLogger,
        private Session $session,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->shouldAudit(request: $request)) {
            return $handler->handle(request: $request);
        }

        try {
            $response = $handler->handle(request: $request);
        } catch (Throwable $exception) {
            $this->record(request: $request, result: 'failure', statusCode: 500);
            throw $exception;
        }

        $this->record(
            request: $request,
            result: $response->status >= 400 ? 'failure' : 'success',
            statusCode: $response->status,
        );

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        if (!str_starts_with($request->path, '/admin')) {
            return false;
        }

        if ($request->path === '/admin/login' || $request->path === '/admin/logout') {
            return false;
        }

        return in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function record(Request $request, string $result, int $statusCode): void
    {
        $this->auditLogger->record(new AuditEvent(
            action: 'cms.http_state_changed',
            resourceType: 'cms_request',
            result: $result,
            actorUserId: $this->actorUserId(),
            ipAddress: $request->server['REMOTE_ADDR'] ?? null,
            userAgent: $request->server['HTTP_USER_AGENT'] ?? null,
            requestId: $request->server['HTTP_X_REQUEST_ID'] ?? null,
            metadata: [
                'method' => $request->method,
                'path' => $request->path,
                'status_code' => $statusCode,
            ],
        ));
    }

    private function actorUserId(): ?string
    {
        $value = $this->session->get(key: 'cms.user_id');

        return is_scalar($value) ? (string) $value : null;
    }
}
```

この例では AuditLogger の例外を握りつぶしていない。

理由は、`PlaceholderAuditLogger` の例外を隠すと、未設定に気付くという placeholder の目的を壊すためである。監査ログ保存失敗時にも業務処理を継続したいアプリケーションでは、`PdoAuditLogger` 側、または別の decorator で failure policy を明示する。

## middleware 登録順

`apps/cms/config/middleware.php` に追加する。

```php
use App\Cms\Infrastructure\Http\Middleware\CmsAuditMiddleware;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;

return [
    ...$sharedMiddleware,
    RequireCmsSessionMiddleware::class,
    CmsAuditMiddleware::class,
];
```

`RequireCmsSessionMiddleware` の後に置く。

理由は、初期 Audit 対象が「ログイン済み CMS 操作」であり、未ログイン redirect やログイン試行を Audit 対象にしないためである。

## PdoAuditLogger 実装方針

DB 保存を行う場合は `PdoAuditLogger` を作る。

配置:

```text
apps/shared/src/Audit/Infrastructure/Persistence/PdoAuditLogger.php
```

依存:

- `PDO`
- 必要なら `PdoHelper`

方針:

- `AuditLoggerInterface` を実装する。
- `AuditEvent` を `audit_logs` へ insert する。
- `before` / `after` / `metadata` は JSON として保存する。
- JSON encode に失敗した場合は例外にする。
- password、token、cookie の除去は原則として呼び出し側の責務にする。
- `PdoAuditLogger` が勝手に request body を読む実装にしない。

container 差し替え例:

```php
use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\Audit\Infrastructure\Persistence\PdoAuditLogger;
use App\Framework\Container\Container;
use PDO;

$container->singleton(
    id: AuditLoggerInterface::class,
    factory: fn (Container $container): AuditLoggerInterface => new PdoAuditLogger(
        pdo: $container->get(id: PDO::class),
    ),
);
```

## Business Audit の追加基準

middleware の `cms.http_state_changed` だけで足りない操作には、UseCase / Controller から明示 Audit を追加する。

追加するべき例:

- 記事作成
- 記事更新
- 記事削除
- 記事復元
- 固定ページ更新
- メディア削除
- サイト設定更新

理由は、middleware は HTTP path と status code しか知らず、業務上の resource id、更新前後、復元元 version などを正確に知らないためである。

Business Audit の例:

```text
action: article.updated
resourceType: article
resourceId: 10
result: success
actorUserId: 1
before:
  status: draft
after:
  status: published
metadata:
  source: cms
```

## テスト手順

### CmsAuditMiddlewareTest

必須テスト:

- GET `/admin/articles` は記録しない。
- POST `/admin/articles/10` は記録する。
- POST `/admin/login` は記録しない。
- POST `/admin/logout` は記録しない。
- POST `/admin/articles/10` の response が 200 / 302 の場合は success として記録する。
- POST `/admin/articles/10` の response が 400 以上の場合は failure として記録する。
- handler が例外を投げた場合は failure を記録し、同じ例外を再 throw する。
- session に `cms.user_id` がある場合は actorUserId に入る。
- request body の `_csrf`、`password` は metadata に入らない。

`PlaceholderAuditLogger` を使うテストでは、対象 request で例外が出ることを確認してよい。middleware 自体の記録内容を検証する場合は、test double の `AuditLoggerInterface` を使う。

### PdoAuditLoggerTest

DB 保存実装を追加する場合の必須テスト:

- `AuditEvent` を `audit_logs` へ保存できる。
- nullable 項目を保存できる。
- `before` / `after` / `metadata` が JSON として保存される。
- JSON encode 不能な値では例外になる。
- 保存した `action`、`resource_type`、`result`、`actor_user_id`、`request_id` を取得して検証できる。

### Container Test

監査ログを必須にするアプリケーションでは、container test を追加する。

- 初期 scaffold または framework sample では `PlaceholderAuditLogger` でよい。
- 実アプリで Audit を有効化する場合は `AuditLoggerInterface` が `PdoAuditLogger` などの実装へ差し替わっていることを検査する。

## 実装順序

1. `docs/audit_middleware.md` を確認する。
2. `CmsAuditMiddlewareTest` を追加する。
3. `CmsAuditMiddleware` を追加する。
4. `apps/cms/config/middleware.php` に `CmsAuditMiddleware::class` を追加する。
5. `PlaceholderAuditLogger` のまま対象 request を実行すると例外になることを確認する。
6. DB 保存が必要な場合だけ `audit_logs` migration と `PdoAuditLogger` を追加する。
7. `apps/shared/config/container.php` を対象アプリの要件に合わせて差し替える。
8. 重要操作に Business Audit が必要な場合だけ、UseCase / Controller へ明示的に `AuditLoggerInterface` 呼び出しを追加する。
9. Docker Compose 経由で lint、PHPUnit、PHPStan を実行する。

## 検証コマンド

ホストに PHP / Composer がない前提のため、Docker Compose 経由で実行する。

```bash
docker compose exec -T app sh -lc 'find apps src tests database public -type f -name "*.php" -print0 | xargs -0 -n1 php -l'
docker compose exec -T app composer test
docker compose exec -T app composer analyse
git diff --check
```

## アーキテクチャ整合性チェック

本設計は次の理由で既存アーキテクチャに反しない。

- `src/Framework` に CMS 固有の Audit middleware を置かないため、Framework がアプリケーション都合を持たない。
- CMS 固有の middleware は `apps/cms/src/Infrastructure/Http/Middleware` に置くため、既存の middleware 配置ルールに合う。
- `AuditLoggerInterface` は shared の差し込み点であり、複数 app から使える基盤機能として妥当である。
- `PdoAuditLogger` は Infrastructure 実装として shared 配下に置くため、Application / Domain から Infrastructure へ依存しない。
- `PlaceholderAuditLogger` を維持するため、フレームワーク初期状態で保存先を強制しない。
- `CmsAuditMiddleware` は `AuditLoggerInterface` に依存し、保存先の具象を知らない。
- Business Audit が必要な場合も、UseCase / Controller から `AuditLoggerInterface` を呼ぶ形にし、HTTP path 解析で業務意味を推測しない。
- HTTP access log、auth/security log、audit log を概念として分けるため、Audit に全ログ責務を押し込まない。

## 機能要件チェック

本設計は次の要件を満たせる。

- CMS の状態変更操作を監査ログとして記録できる。
- 実行ユーザー、結果、IP、user agent、request id、method、path、status code を記録できる。
- GET を初期対象外にし、必要な GET だけ後から明示的に追加できる。
- CMS ログインを Audit 対象外にし、必要なら auth/security log として別設計にできる。
- Placeholder により、Audit 未設定のまま監査対象処理を実行した場合に例外で気付ける。
- DB 保存が必要なアプリケーションでは `PdoAuditLogger` へ差し替えられる。
- before / after が必要な操作は Business Audit として追加できる。
- request body 全体や password、CSRF token、cookie、session id を保存しない方針により、不要な秘密情報保存を避けられる。

## 採用しないこと

初期実装では以下を採用しない。

- `NullAuditLogger` を標準にすること。
- 全 GET request を監査ログへ保存すること。
- CMS ログイン成功・失敗を Audit として保存すること。
- HTTP access log と Audit log を同じ middleware に統合すること。
- request body 全体を Audit log に保存すること。
- resource id や before / after を middleware が path や body から推測すること。
- request attributes や CurrentUser を、この作業だけのために先行導入すること。
