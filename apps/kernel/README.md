# Kernel

Kernel はこのプロジェクトの HTTP application を合成する層です。

Framework は `Request`、`Response`、`Router`、`Container`、middleware pipeline、runtime などの部品だけを提供します。`cms`、`front`、将来の `api` や `preview` のようなアプリケーション構成は Kernel 側で定義します。

## アプリケーション定義

`apps/kernel/config/application.php` で `ApplicationDefinition` を追加します。

```php
new ApplicationDefinition(
    name: 'api',
    pathPrefix: '/api',
    routesPath: '/apps/api/config/routes.php',
    containerPath: '/apps/api/config/container.php',
    middlewarePath: '/apps/api/config/middleware.php',
),
```

`pathPrefix` が長い定義から優先して選択されます。たとえば `/api` は `/` より先に判定されます。

各アプリは次の config を持ちます。

- `routes.php`
- `container.php`
- `middleware.php`
