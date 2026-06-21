# CMS Policy 実装計画

## 目的

Guard が入ったことで、CMS の認可を次の段階へ進められる状態になった。

Guard は「誰がログインしているか」を解決する。Policy は、その認証済みユーザーが「この対象に対して、この操作をしてよいか」を判定する。

このため Policy は Guard の上に直接載せるのではなく、まず `Permission` / `Role` / `AuthorizationService` を最小実装し、その上で対象リソースの状態を見たい操作から追加する。

## 現状

- `CurrentCmsUser` と `CmsGuard` は実装済み。
- `RequireCmsSessionMiddleware` は `CmsGuard::isAuthenticated()` を使っている。
- `CmsGuard` は Permission / Role / Policy 判定をしない。
- CMS の route は `apps/cms/config/routes.php` の単純な配列で定義されている。
- CMS middleware は `apps/cms/config/middleware.php` で全体適用されている。
- `CmsAuditMiddleware` は HTTP envelope の監査であり、業務イベントの Audit はまだ未実装。

## 判断

`RequireCmsSessionMiddleware` は当面名前を維持する。

理由:

- すでに Guard へ委譲済みで、責務は「CMS session 必須」に限定されている。
- 今すぐ `CmsAuthMiddleware` へ改名しても認可実装上の効果は小さい。
- route-level permission middleware 追加時に、middleware 名や config の見通しが悪くなった段階で改名を検討すればよい。

Policy は最初から全リソース分を作らない。

理由:

- 空の Policy は置き場所だけ増えて、実際の判断が Controller / UseCase / template に散る原因になる。
- 現時点で Policy が明確に必要なのは、記事の所有者・状態・公開操作のようなリソース依存条件が出る箇所である。
- まずは `ArticlePolicy` を候補にし、固定ページ、メディア、分類、問い合わせ、設定は Permission だけで不足が出た段階で追加する。

## 用語

Permission:
操作権限。例: `article.update`、`media.delete`。

Role:
Permission の集合。例: `admin`、`editor`、`writer`、`viewer`。

AuthorizationService:
CurrentCmsUser と Permission を受け取り、大まかな操作権限を判定する入口。

Policy:
CurrentCmsUser と対象リソースを受け取り、所有者、状態、公開可否などを含めて判定する部品。

## 最初の権限セット

初期 Permission は画面名ではなく操作名にする。

```text
dashboard.view
article.list
article.create
article.update
article.delete
article.restore
article.publish
article.preview
article.history
page.list
page.create
page.update
taxonomy.manage
media.list
media.upload
media.delete
inquiry.read
setting.update
trash.view
```

初期 Role はコード上の mapping とし、DB 化や管理画面は作らない。

```text
admin:
  全 Permission

editor:
  dashboard.view
  article.list
  article.create
  article.update
  article.restore
  article.publish
  article.preview
  article.history
  page.list
  page.create
  page.update
  taxonomy.manage
  media.list
  media.upload
  media.delete
  inquiry.read
  trash.view

writer:
  dashboard.view
  article.list
  article.create
  article.update
  article.preview
  article.history
  media.list
  media.upload

viewer:
  dashboard.view
  article.list
  article.preview
  article.history
  page.list
  media.list
  inquiry.read
```

要相談:

- `viewer` に問い合わせ閲覧 `inquiry.read` を許可するか。
- `writer` に既存記事の更新を許可する場合、「自分の記事のみ」にするか。
- `editor` に設定更新 `setting.update` を許可するか。初期案では admin のみに寄せる。
- `article.delete` は admin のみにするか、editor まで許可するか。初期案では admin のみに寄せる。

## 配置方針

CMS 固有の認可なので `src/Framework` には置かない。

候補:

```text
apps/cms/src/Application/Auth/Authorization/
  AuthorizationService.php
  AuthorizationException.php
  Permission.php
  RolePermissions.php

apps/cms/src/Application/Article/Policy/
  ArticlePolicy.php
  ArticlePolicyArticle.php

apps/cms/src/Infrastructure/Http/Middleware/
  RequireCmsPermissionMiddleware.php
```

`AuthorizationService` は `Application/Auth/Authorization` に置く。

理由:

- middleware、Controller、UseCase の共通入口になる。
- CMS の業務認可であり、Framework の責務ではない。
- DB や HTTP に依存しないコード上 mapping から始められる。

Policy は対象の Application 配下に置く。

理由:

- 記事の状態や所有者を使う判定は Article のユースケースに近い。
- `Application/Auth` に全 Policy を集めると、リソース固有ルールの置き場が遠くなる。

## 実装順

### 1. Permission / Role mapping を追加する

- `Permission` は string backed enum ではなく、まず定数クラスにする。
- PHP 8.5 前提でも、既存コードとの馴染みとテスト容易性を優先する。
- `RolePermissions` は `role => permission[]` の private mapping を持つ。
- 未知 role は権限なしとして扱う。
- `admin` の全許可は暗黙にせず、テストで見える形にする。

検証:

- role ごとの許可 Permission。
- 未知 role の拒否。
- Permission 名の重複がないこと。

### 2. AuthorizationService を追加する

最低限の API:

```php
can(CurrentCmsUser $user, string $permission): bool
authorize(CurrentCmsUser $user, string $permission): void
```

`authorize()` は拒否時に `AuthorizationException` を投げる。

例外には最低限、permission と user id を持たせる。HTTP response への変換は middleware / Controller / exception handler 側の責務にする。

検証:

- 許可 Permission で例外が出ない。
- 拒否 Permission で `AuthorizationException` が出る。
- 例外から permission と user id を参照できる。

### 3. route-level permission check を追加する

既存 route 定義を大きく壊さないため、最初は route metadata 改修を急がない。

第一候補:

- `RequireCmsPermissionMiddleware` を追加する。
- middleware 内に `method + path pattern => permission` の mapping をコードで持つ。
- まず CMS の書き込み系 route だけを対象にする。

対象例:

```text
POST /admin/articles               article.create
POST /admin/articles/{id}          article.update
POST /admin/articles/{id}/delete   article.delete
POST /admin/articles/{id}/restore  article.restore
POST /admin/pages                  page.create
POST /admin/pages/{id}             page.update
POST /admin/categories             taxonomy.manage
POST /admin/categories/{id}        taxonomy.manage
POST /admin/categories/{id}/delete taxonomy.manage
POST /admin/tags                   taxonomy.manage
POST /admin/tags/{id}              taxonomy.manage
POST /admin/tags/{id}/delete       taxonomy.manage
POST /admin/media                  media.upload
POST /admin/media/{id}/delete      media.delete
POST /admin/settings               setting.update
```

この段階では route-level check は粗い判定に限定する。

検証:

- 未ログインは既存 middleware で redirect。
- ログイン済みだが権限なしは 403。
- 権限ありは次の handler へ進む。
- route mapping 対象外は permission middleware では止めない。

### 4. Policy の最初の対象を Article に絞る

`ArticlePolicy` は Permission だけでは判断できない条件が必要になった時点で実装する。

初期候補:

```text
canUpdate(CurrentCmsUser $user, ArticlePolicyArticle $article): bool
canPublish(CurrentCmsUser $user, ArticlePolicyArticle $article): bool
canDelete(CurrentCmsUser $user, ArticlePolicyArticle $article): bool
canRestore(CurrentCmsUser $user, ArticlePolicyArticle $article): bool
```

`ArticlePolicyArticle` は Policy 判定に必要な最小読み取り model にする。

候補フィールド:

```text
id
status
authorUserId
createdByUserId
updatedByUserId
publishedAt
publishedEndsAt
deletedAt
```

要相談:

- 現在の `articles` table に CMS user id の所有者カラムを追加するか。
- 既存の `author_name` は表示名であり、認可の所有者判定には使わない方がよい。
- 所有者概念をまだ入れないなら、初期 `ArticlePolicy` は公開・削除・復元の状態判定に絞る。

### 5. Article UseCase に Policy check を入れる

優先順:

1. `Article/Save`
2. `Article/Delete`
3. `Article/Restore`

UseCase Input に `CurrentCmsUser` そのものを入れるか、`actorUserId` / `actorRole` を入れるかは実装前に決める。

初期案:

- Application 内で閉じるため、`CurrentCmsUser` を Input に含める。
- Controller は `CmsGuard::currentUser()` で actor を取り出し、Input へ渡す。
- 未ログインは route middleware で止まる前提だが、UseCase 側も actor 必須にする。

検証:

- route middleware を通らず UseCase を直接呼んでも拒否される。
- 公開操作は `article.publish` と Policy の両方を満たす必要がある。
- 削除操作は `article.delete` と Policy の両方を満たす必要がある。

### 6. Latte can helper は後回しにする

UI 表示制御は重要だが、強制力はない。

順序:

1. server-side の強制チェック
2. template helper による表示制御

`can()` helper を追加する時は、request ごとの `CurrentCmsUser` と `AuthorizationService` を参照する。

注意:

- 判定結果を worker lifetime singleton に保持しない。
- View compiled cache に認可結果を入れない。

### 7. Audit は認可拒否から入れる

初期の Business Audit 候補:

```text
authorization.denied
article.updated
article.deleted
article.restored
article.published
```

ただし、`PlaceholderAuditLogger` のまま重要操作へ接続すると設定漏れ検知で例外になる。

そのため、Business Audit は次のどちらかを決めてから入れる。

- CMS container で実保存用 AuditLogger を bind する。
- 初期は認可拒否の通常ログだけにして、Business Audit 接続は別作業にする。

## 実装しないこと

- Role / Permission の DB 化。
- Role / Permission 管理画面。
- Group / Team / Site 単位の制御。
- 承認ワークフロー。
- 全リソース分の空 Policy 作成。
- Framework 配下への CMS 固有認可の追加。

## 話し合いたい論点

1. writer は「自分の記事だけ編集可」にするか、それとも所有者カラム追加までは記事編集不可に寄せるか。
2. article の所有者を表す DB カラム名をどうするか。候補は `created_by_user_id`、`owner_user_id`、`author_user_id`。
3. `article.delete` は admin のみにするか、editor まで許可するか。
4. `viewer` に `inquiry.read` を許可するか。
5. route-level permission mapping を middleware 内に置く短期案で始めるか、先に route metadata を導入するか。
6. UseCase Input に `CurrentCmsUser` を渡すか、actor 用の小さい value を別に作るか。

## 次の一手

まずは Policy そのものではなく、Policy を呼べる認可基盤を作る。

実装単位:

1. Permission / Role mapping
2. AuthorizationService
3. RequireCmsPermissionMiddleware
4. ArticlePolicy の設計確定
5. Article Save / Delete / Restore への UseCase-level authorization

この順番なら、Guard で解決した現在ユーザーを使って、route-level check と UseCase / Policy check の二段階強制へ自然につなげられる。
