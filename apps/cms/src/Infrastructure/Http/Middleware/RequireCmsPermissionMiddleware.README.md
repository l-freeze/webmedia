# RequireCmsPermissionMiddleware

`RequireCmsPermissionMiddleware` は route metadata の `requires_permission` を読み、ログイン中 CMS ユーザーがその Permission を持っているかを確認します。

## 初期設定

CMS permission middleware を有効にするには、次の3点を揃えます。

1. `apps/cms/config/middleware.php` に `RequireCmsPermissionMiddleware::class` を登録する。
2. `apps/cms/config/routes.php` の保護したい route に `requires_permission` metadata を設定する。
3. `apps/cms/config/container.php` で Guard / Query / AuthorizationService の依存が解決できるようにする。

`AuthorizationService` は `IRolePermissionQuery` に依存します。現状は `CodeDefinedRolePermissionQuery` を container で bind しており、コード上の permission mapping から判定します。将来 DB 化する場合は `PdoRolePermissionQuery` のような実装へ差し替えます。

## 登録順

標準順序は次の通りです。

```text
RequireCmsSessionMiddleware
  -> RequireCmsPermissionMiddleware
  -> CmsAuditMiddleware
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

metadata は任意です。認可が必要な route には `requires_permission` を付けます。

```php
use App\Cms\Application\Auth\Authorization\Permission;
use App\Cms\Presentation\Http\Controller\Article\Update\Controller as ArticleUpdateController;

return [
    [
        'POST',
        '/admin/articles/{id:\d+}',
        ArticleUpdateController::class,
        ['requires_permission' => Permission::ARTICLE_UPDATE],
    ],
];
```

`requires_permission` が未設定の route は、この middleware では止めません。

命名として `can` は使いません。`can` は「できるかどうか」を返す関数名としては短すぎ、route metadata としては何を要求しているのかが曖昧なためです。この CMS では、route が要求する Permission であることが分かる `requires_permission` に統一します。

## 拒否時の挙動

- 未ログインの場合は `/admin/login` へ redirect する。
- ログイン済みだが Permission 不足の場合は `403 Forbidden` を返す。
- どちらも `SecurityLoggerInterface` に security event を記録する。
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

## 書き込み Route 追加時の確認

- route に `requires_permission` が必要か。
- 既存 Permission で表現できるか。
- 新しい Permission を追加するなら `Permission`、`CodeDefinedRolePermissionQuery`、テストを同じ変更単位で更新したか。
- route-level check だけで足りないリソース状態判定があるなら、UseCase / Policy へ追加したか。
