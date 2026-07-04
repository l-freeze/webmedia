# リソース権限の整理プラン

## 目的

CMS の route-level permission と、記事などの resource-level permission を分離する。

現在は `Application/Auth/Policy` に route-level permission と Article 固有の操作権限が混在している。これにより、以下の問題がある。

- `Auth` が Article 固有の概念を持っている。
- `Article` UseCase が `Auth/Policy` を直接呼んでいる。
- `Application/Auth/Policy` が UseCase ではないのに Application 配下へ残っている。
- `Policy` という名前が、認可判定部品なのか、管理対象としての Policy 機能なのか曖昧になっている。

このプランでは、route-level permission は middleware 側、resource-level permission は Domain 側に寄せる。

## 方針

### Route-Level Permission

管理画面の route に入れるかどうかは middleware が判定する。

対象:

```text
この管理機能に入れるか
この route を実行できる role か
```

例:

```text
POST /admin/articles/{id}
  requires_permission = article.update
```

これは `RequireCmsPermissionMiddleware` が見る。

### Resource-Level Permission

特定 resource に対して、その actor がその操作をしてよいかは resource 側の Domain rule として扱う。

`Article/Save`、`Article/Delete`、`Article/Restore` は CQRS の C 側である。Application UseCase は入口として処理を調停するが、コマンド側のモデルと永続化境界は Domain レイヤーに置く。

ここでいう `Query` は Application の表示用 Query port を指す。SELECT だけで完結し、状態変更を伴わない処理は Q 側である。一方、後続で状態変更する aggregate/entity を復元するための SELECT は、C 側の Repository 責務である。

同じ復元でも、履歴表示や版プレビューは Q 側であり、`Article/History` や `ArticleVersion/Preview` の Query port が扱う。POST で現在の記事を過去版へ戻す `Article/Restore` は C 側である。

対象:

```text
この actor が、この article を更新できるか
この actor が、この article を削除できるか
この actor が、この article を復元できるか
```

これは `ArticlePolicy` が判定する。ただし `ArticlePolicy` は `PermissionCode` を知らない。

見る情報は以下に限定する。

```text
actor id
actor role
article id
article author id
article status
```

## 現在の関連ツリー

```text
apps/cms/src/Application/
  Auth/
    Policy/
      ArticlePolicy.php
      AuthorizationService.php
      RoleHierarchy.php
      Exception/
        AuthorizationException.php
      Query/
        IArticlePolicyQuery.php
        IRolePermissionQuery.php
      Value/
        ArticlePolicyArticle.php
        PermissionCode.php
        RoleCode.php

  Article/
    Save/
      Input.php
      Interactor.php
      Output.php
    Delete/
      Input.php
      Interactor.php
      Output.php
    Restore/
      Input.php
      Interactor.php
      Output.php

apps/cms/src/Infrastructure/Persistence/
  PdoArticlePolicyQuery.php
```

現在の依存:

```text
Article/Save/Interactor
  -> Auth/Policy/ArticlePolicy
  -> Auth/Policy/Query/IArticlePolicyQuery
  -> Auth/Policy/Value/PermissionCode

Article/Delete/Interactor
  -> Auth/Policy/ArticlePolicy
  -> Auth/Policy/Value/PermissionCode

Article/Restore/Interactor
  -> Auth/Policy/ArticlePolicy
  -> Auth/Policy/Value/PermissionCode
```

この依存は解消する。

## 修正後の関連ツリー

```text
apps/cms/src/Application/
  Auth/
    AuthorizeRoute/
      Input.php
      Interactor.php
      Output.php
      Query/
        IRolePermissionQuery.php
      Value/
        PermissionCode.php
        RoleCode.php

  Article/
    Save/
      Input.php
      Interactor.php
      Output.php

    Delete/
      Input.php
      Interactor.php
      Output.php

    Restore/
      Input.php
      Interactor.php
      Output.php

apps/cms/src/Domain/
  Repository/
    IArticleRepository.php
  Policy/
    ArticlePolicy.php
  Value/
    ArticleUpdatePermission.php
    ArticleDeletePermission.php
    ArticleRestorePermission.php
    CmsActor.php
    CmsRole.php

apps/cms/src/Infrastructure/Persistence/
  PdoArticleRepository.php
```

## 責務

### `Auth/AuthorizeRoute`

route-level permission だけを見る Application UseCase。

```text
Input:
  actor role
  required permission

Output:
  allowed / denied
```

`PermissionCode` と `RoleCode` はここに置く。

`Auth/AuthorizeRoute` は Article を知らない。

### `Domain/Repository/IArticleRepository`

C 側の `Article/Save`、`Article/Delete`、`Article/Restore` は Application Query port を持たない。

Article の取得、状態変更、保存は Domain Repository と Article Entity で扱う。コマンド側の読み込みは表示用 Query ではなく、状態変更する aggregate/entity を復元するための Repository 責務である。

```text
Article/Save/Interactor
  -> IArticleRepository で Article を取得する
  -> ArticlePolicy で許可済み操作 value を発行する
  -> Article Entity に許可済み操作 value を渡して更新する
  -> IArticleRepository で保存する

Article/Delete/Interactor
  -> IArticleRepository で Article を取得する
  -> ArticlePolicy で許可済み操作 value を発行する
  -> Article Entity に許可済み操作 value を渡して削除状態へ変更する
  -> IArticleRepository で保存する

Article/Restore/Interactor
  -> IArticleRepository で復元に必要な Article aggregate/entity を復元する
  -> ArticlePolicy で許可済み操作 value を発行する
  -> Article Entity に許可済み操作 value を渡して復元する
  -> IArticleRepository で保存する
```

既存の `Article/Edit`、`Article/List`、`Article/Preview` の `Query` は読み取り画面用なので対象外とする。

### `Domain/Policy/ArticlePolicy`

Article resource に対する操作可否を判定する Domain policy。

禁止:

```text
PermissionCode を参照する
IRolePermissionQuery を呼ぶ
DB を読む
Request / Session / Response を知る
```

許可:

```text
CmsActor を見る
Article の状態を使う
許可済み操作 value を発行する
```

### 許可済み操作 Value

Policy が判定済みの proof / capability として発行する。

```text
ArticleUpdatePermission
ArticleDeletePermission
ArticleRestorePermission
```

Article Entity は状態変更時に、この許可済み操作 value を受け取る。

```php
$permission = $policy->authorizeUpdate(actor: $input->actor, target: $target);

$article->update(
    permission: $permission,
    title: $title,
    body: $body,
);
```

Entity は `ArticlePolicy` そのものではなく、許可済み操作 value を受け取る。

## 処理フロー

### 記事更新

```text
RequireCmsPermissionMiddleware
  -> route metadata の article.update を確認

Article Update Controller
  -> current user を CmsActor として Input へ詰める

Article/Save/Interactor
  -> IArticleRepository で Article を取得する
  -> ArticlePolicy::authorizeUpdate() を呼ぶ
  -> ArticleUpdatePermission を受け取る
  -> Article Entity に ArticleUpdatePermission を渡して更新する
  -> IArticleRepository で保存する
```

### 記事削除

```text
RequireCmsPermissionMiddleware
  -> route metadata の article.delete を確認

Article/Delete/Interactor
  -> IArticleRepository で Article を取得する
  -> ArticlePolicy::authorizeDelete() を呼ぶ
  -> ArticleDeletePermission を受け取る
  -> Article Entity に ArticleDeletePermission を渡して削除する
  -> IArticleRepository で保存する
```

### 記事復元

```text
RequireCmsPermissionMiddleware
  -> route metadata の article.restore を確認

Article/Restore/Interactor
  -> IArticleRepository で復元に必要な Article aggregate/entity を復元する
  -> ArticlePolicy::authorizeRestore() を呼ぶ
  -> ArticleRestorePermission を受け取る
  -> Article Entity に ArticleRestorePermission を渡して復元する
  -> IArticleRepository で保存する
```

## 移行手順

1. `Domain/Value/CmsActor.php` と `Domain/Value/CmsRole.php` を追加する。
2. `Auth/GetCurrentUser` の Output を `CurrentCmsUser` から `CmsActor` へ寄せる。
3. `Auth/AuthorizeRoute` UseCase を追加する。
4. `RequireCmsPermissionMiddleware` を `AuthorizationService` 直接利用から `Auth/AuthorizeRoute/Interactor` 利用へ変更する。
5. `IArticleRepository` を、C 側で Article aggregate/entity を取得して保存できる形へ拡張する。
6. `Domain/Policy/ArticlePolicy.php` を追加する。
7. `ArticleUpdatePermission`、`ArticleDeletePermission`、`ArticleRestorePermission` を追加する。
8. `Article` Entity の更新 / 削除 / 復元操作が許可済み操作 value を受け取る形へ変更する。
9. `Article/Save`、`Article/Delete`、`Article/Restore` から `Auth/Policy` 依存を削除する。
10. `Auth/Policy/ArticlePolicy`、`IArticlePolicyQuery`、`ArticlePolicyArticle`、`PdoArticlePolicyQuery` を削除する。
11. `Auth/Policy` ディレクトリを廃止し、残りを `Auth/AuthorizeRoute` へ移す。
12. Architecture Test を更新し、`Application/Auth/Policy` 例外を削除する。
13. PHPUnit / PHPStan を実行する。

## 完了条件

```text
Article UseCase が App\Cms\Application\Auth\Policy を参照していない
Auth が Article 固有の Query / Value / Policy を持っていない
Application/Auth/Policy が存在しない
Application/<Feature>/<Action> ルールが例外なしで通る
Article/Save、Article/Delete、Article/Restore が Application Query port を持たない
route-level permission は middleware で止まる
resource-level permission は Domain/Policy/ArticlePolicy が判定する
Article Entity の状態変更は許可済み操作 value を要求する
```

## 保留判断

`CmsActor` と `CmsRole` を Domain Value とするか、CMS Application 共通 value とするかは実装前に最終確認する。

現時点の推奨は Domain Value。理由は Article Entity / Domain Policy が参照するため、Domain から Application へ依存させないためである。
