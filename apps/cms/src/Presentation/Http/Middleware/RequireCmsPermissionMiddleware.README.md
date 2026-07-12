# RequireCmsPermissionMiddleware

`RequireCmsPermissionMiddleware` は、自身の FQCN を key とする route metadata を読み、ログイン中 CMS ユーザーがその Permission を持っているかを確認します。

## 初期設定

CMS permission middleware を有効にするには、次の3点を揃えます。

1. `apps/cms/config/middleware.php` に `RequireCmsPermissionMiddleware::class` を登録する。
2. `apps/cms/config/routes.php` の保護したい route に `RequireCmsPermissionMiddleware::class => PermissionCode` を設定する。
3. `apps/cms/config/container.php` で Guard / Query / `Auth/AuthorizeRoute` の依存が解決できるようにする。

`Auth/AuthorizeRoute/Interactor` は `Auth/AuthorizeRoute/Query/IRolePermissionQuery` に依存します。現状は `Infrastructure/Auth/Authorization/CodeDefinedRolePermissionQuery` を container で bind しており、コード上の permission mapping から判定します。将来 DB 化する場合は `PdoRolePermissionQuery` のような実装へ差し替えます。

## 登録順

標準順序は次の通りです。

```text
RequireCmsSessionMiddleware
  -> RequireCmsPermissionMiddleware
  -> AuditMiddleware
  -> Controller
```

session middleware が先に未ログイン request を止め、permission middleware がログイン済みユーザーの大まかな route 権限を止めます。

## Route Metadata

CMS の route entry は次の形式を基本にします。

```php
[
    method,
    path,
    controller,
    metadata,
]
```

metadata は任意です。top-level key は設定を消費する middleware の FQCN とし、値の型と意味はその middleware が所有します。認可が必要な route には `RequireCmsPermissionMiddleware::class` を key として Permission を設定します。

```php
use App\Cms\Application\Auth\AuthorizeRoute\Value\PermissionCode;
use App\Cms\Presentation\Http\Controller\Article\Update\Controller as ArticleUpdateController;
use App\Cms\Presentation\Http\Middleware\RequireCmsPermissionMiddleware;

return [
    [
        'POST',
        '/admin/articles/{id:\d+}',
        ArticleUpdateController::class,
        [RequireCmsPermissionMiddleware::class => PermissionCode::ARTICLE_UPDATE],
    ],
];
```

`RequireCmsPermissionMiddleware::class` が未設定の route は、この middleware では止めません。CMS routes では login の GET / POST と logout だけを例外とし、それ以外は表示用 GET を含めて Permission 設定を必須にします。FQCNをkeyにすることで、文字列keyの衝突を避け、設定の所有者をroute定義上で明示します。

## 拒否時の挙動

- 未ログインの場合は `/admin/login` へ redirect する。
- ログイン済みだが Permission 不足の場合は `403 Forbidden` を返す。
- どちらも `EventBusInterface` へ security event を dispatch する。
  - 未ログイン: `cms.authorization.unauthenticated`
  - Permission 不足: `cms.authorization.denied`

## 責務

この middleware は route-level の粗い認可だけを担当します。

できること:

- この route に入ってよい role かを止める。
- 設定更新、メディア削除、記事削除などの大まかな操作権限を止める。

できないこと:

- writer が「自分の記事だけ」編集しているか。
- 公開済み記事、所有者、状態、カテゴリなどを見た詳細判定。
- UseCase を Controller 以外から直接呼んだ時の最終防御。

対象リソースの状態を見る判定は UseCase / Policy 側で行います。たとえば記事更新では、route-level で `article.update` を通した後、`ArticlePolicy` が `author_id` と role hierarchy を見て、writer は自分の記事だけ更新できるようにします。

## Route 追加時の確認

- login / logout 以外の route に `RequireCmsPermissionMiddleware::class` を設定したか。
- 既存 Permission で表現できるか。
- 新しい Permission を追加するなら `PermissionCode`、`CodeDefinedRolePermissionQuery`、テストを同じ変更単位で更新したか。
- route-level check だけで足りないリソース状態判定があるなら、UseCase / Policy へ追加したか。
