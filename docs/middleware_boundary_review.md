# Middleware Boundary Review

## 目的

CMS middleware 追加で曖昧になりやすい境界を整理し、今すぐ直すものと保留するものを分ける。

## HTTP Request Actor Context

HTTP request actor context は、request 処理中の実行主体を一貫して参照するための request-scoped な context である。

例:

```php
final readonly class RequestActorContext
{
    public function __construct(
        public ?string $actorType,
        public ?int $cmsUserId,
        public ?string $cmsRole,
        public ?string $displayName,
    ) {
    }
}
```

使い道:

- Audit / SecurityLog / Business event で同じ actor 表現を使う。
- HTTP middleware、Controller、UseCase entry が session key をそれぞれ解釈しない。
- 将来、CMS user 以外の batch / system / importer / impersonation を同じ形で表現しやすくする。

一般的な考え方としては、request context、current user context、principal、actor、execution context と呼ばれる設計に近い。Web framework や enterprise application では珍しい考え方ではない。ただし、このプロジェクトの現段階では `CmsGuard` で現在ユーザーを取れば足りるため、専用 context はまだ作らない。

現行方針:

- HTTP request の actor が CMS user だけで足りる間は `CmsGuard::currentUser()` を使う。
- batch / system / importer / impersonation など、CMS user 以外の主体が必要になった時点で `RequestActorContext` または `ActorContext` を検討する。

## Route Metadata Attribute

### 何が問題か

`RequireCmsPermissionMiddleware` は route metadata の `requires_permission` を読むために、Framework `Router::ROUTE_METADATA_ATTRIBUTE` を直接参照している。

これは動作上は問題ないが、CMS 認可 middleware が Router の request attribute 格納方式を知っている。将来 Router の match 結果表現を変える場合、CMS middleware 側も影響を受ける。

### 改善手段

1. `MatchedRoute` value を request attribute に載せる。

```php
final readonly class MatchedRoute
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public array $metadata,
    ) {
    }

    public function requiredPermission(): ?string
    {
        $permission = $this->metadata['requires_permission'] ?? null;

        return is_string($permission) && $permission !== '' ? $permission : null;
    }
}
```

2. `RouteMetadataReader` のような小さい service に閉じ込める。

```php
final readonly class RouteMetadataReader
{
    public function requiredPermission(Request $request): ?string
    {
        $metadata = $request->attribute(key: Router::ROUTE_METADATA_ATTRIBUTE, default: []);
        if (!is_array($metadata)) {
            return null;
        }

        $permission = $metadata['requires_permission'] ?? null;

        return is_string($permission) && $permission !== '' ? $permission : null;
    }
}
```

3. `Request` に route metadata 用 accessor を足す。

```php
$request->routeMetadata('requires_permission');
```

ただし `Request` が routing の概念を持ち始めるため、Framework の責務として妥当か検討が必要。

### 現行判断

保留。現時点では route metadata の利用箇所が `RequireCmsPermissionMiddleware` だけで、抽象化を先に増やすほどではない。次に route metadata を読む middleware が増えた時点で、`MatchedRoute` value か `RouteMetadataReader` を検討する。

## HTTP Request Metadata Extraction

### 何が問題か

IP address、user-agent、request-id の抽出が複数箇所に散っている。

該当例:

- CSRF security event
- permission denied security event
- login security event
- logout security event
- audit event

このままだと、trusted proxy 対応、request id header 名の変更、user-agent の正規化などを入れる時に複数箇所を修正する必要がある。

### 改善手段

1. `RequestLogContext` value を作る。

```php
final readonly class RequestLogContext
{
    public function __construct(
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $requestId,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            ipAddress: self::serverString($request, 'REMOTE_ADDR'),
            userAgent: self::serverString($request, 'HTTP_USER_AGENT'),
            requestId: self::serverString($request, 'HTTP_X_REQUEST_ID'),
        );
    }
}
```

長所:

- 小さい。
- EventBus / Audit / SecurityLog のどれにも寄りすぎない。
- trusted proxy 対応をここへ閉じ込められる。

短所:

- 各 event 作成箇所で `RequestLogContext::fromRequest()` は呼ぶ必要がある。

2. `SecurityLogEventFactory` / `AuditEventFactory` を作る。

長所:

- event 作成の重複も減る。

短所:

- event 種別ごとの metadata が増えると factory が太りやすい。
- Audit と SecurityLog の責務をまたいだ万能 factory になりやすい。

3. `RequestContextMiddleware` で request attribute に載せる。

長所:

- 後続 middleware / controller が同じ context を読める。

短所:

- request attribute 依存が増える。
- context が必要ない request でも middleware が動く。

### 推奨

まずは `RequestLogContext::fromRequest()` を shared か CMS 側に追加するのがよい。現時点では security / audit の両方で使うため、`apps/shared/src/Http/RequestLogContext.php` または `apps/shared/src/Logging/Domain/RequestLogContext.php` が候補。

ただし `trusted proxy` の設計がないうちは、`X-Forwarded-For` を無条件に信用しない。現行どおり `REMOTE_ADDR` を基本にする。

## Route Match Twice

### 現状

`Application` は middleware 実行前に `Router::withRouteMetadata()` を呼び、最後の destination で `Router::dispatch()` を呼ぶ。そのため route match が2回走る。

### 2回走らせる利点

- 実装が単純。
- middleware は metadata だけを見て、Controller dispatch の詳細を知らない。
- route not found / method not allowed の処理は `Router::dispatch()` に残る。
- 現在の route 数では性能問題になりにくい。

### 問題点

- 同じ request に対して route match が重複する。
- metadata と dispatch の match 結果が理論上は別物として扱われる。
- 将来、route match 結果に route name、vars、metadata、controller をまとめて載せたい場合に再設計が必要。

### 改善手段

1. `MatchedRoute` を一度だけ作り、request attribute に載せる。

```php
$matched = $router->match($request);
$request = $request->withAttribute(MatchedRoute::class, $matched);
```

2. `Router::dispatchMatched()` を追加し、最後は match 済み route を使う。

```php
$router->dispatchMatched($request, $container, $matched);
```

3. 現状維持。

### 現行判断

現状維持でよい。理由は、現行の2回 match は理解しやすく、route 数も少なく、middleware が route dispatch の詳細を知らずに済んでいるため。

改善対象にする条件:

- route metadata を読む middleware が増える。
- route vars を middleware でも使いたくなる。
- route 数が増えて match コストが実測で問題になる。
- `MatchedRoute` value を導入して route metadata attribute 問題も同時に解消したくなった時。
