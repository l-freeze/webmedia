# CsrfMiddleware

`CsrfMiddleware` は Framework 標準の CSRF 検証 middleware です。

## 責務

- `POST` / `PUT` / `PATCH` / `DELETE` の state-changing request だけを検証する。
- body の `_csrf` token だけを読む。
- `excludedPaths` と `enabled` の設定に従って検証を省略する。
- token が不正なら `422 Invalid CSRF token` を返す。

Framework 側の実装は CMS / Front / Shared のログや audit を知りません。

## アプリ側での差し替え

アプリケーションが CSRF 失敗を security log に記録したい場合など、標準挙動へアプリ固有の副作用を足す必要がある場合は、アプリ側 container で `CsrfMiddleware::class` の binding を差し替えてよいです。

現行 CMS は `apps/shared/config/middleware.php` では `CsrfMiddleware::class` を middleware list に載せたまま、`apps/cms/config/container.php` で同じ service id を `CsrfSecurityLogMiddleware` に差し替えます。

```php
$container->singleton(
    id: CsrfMiddleware::class,
    factory: fn (Container $container): CsrfSecurityLogMiddleware => new CsrfSecurityLogMiddleware(...),
);
```

この方式では middleware list の共通性を維持しつつ、アプリ固有の挙動だけを container で差し替えられます。

注意点:

- 差し替え先も `IMiddleware` を実装する。
- 差し替え先は Framework ではなくアプリ側に置く。
- Framework の `CsrfMiddleware` に CMS 固有 logger や EventBus を注入しない。
- 差し替えた場合は、アプリ側 middleware README または container 付近の README に明記する。

## 重複実装について

標準 `CsrfMiddleware` と差し替え先 middleware は、CSRF 対象 method や excluded path 判定を共有したくなる場合があります。

ただし、そのためだけに Framework 側へ CMS 固有の hook や listener を増やすと、差し替えよりも分かりにくくなります。共通化する場合は、まず Framework 側に副作用を持たない `CsrfRequestMatcher` のような小さい value/service を切り出すかどうかを検討します。現時点では差し替えを README で明示し、過剰な hook は追加しません。
