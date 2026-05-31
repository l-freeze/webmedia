# 権限管理と承認ワークフロー拡張

## 目的

このドキュメントは、CMS に多人数運用、ロール別権限管理、承認ワークフローを追加する場合の設計方針をまとめる。

これらは別々の機能というより、実務上はひとつの運用要件として扱う。

```text
多人数運用
  -> ロールと権限
  -> 認可ポリシー
  -> 承認ワークフロー
  -> 監査ログ
```

段階的に実装することはできるが、設計はまとめて考える必要がある。UI のボタンを隠すだけの実装では不十分。

## 現在のコードベースとの相性

今のアーキテクチャには、この拡張を入れる余地がある。ただし、慎重に入れないと権限判定が散らばりやすい。

相性が良い点:

- Controller が薄い。
- UseCase が Application Interactor として分離されている。
- Session、CSRF、Request、Routing が Framework 側に集約されている。
- CMS と Front が別アプリケーションコンテキストとして分かれている。
- 通常ログと監査ログの interface が `src/Framework/Logging` に用意されている。

難しいのはクラス追加そのものではない。難しいのは、認可ルールが routes、Controller、template、UseCase にバラバラに散らばることを防ぐこと。

## 中核概念

### CurrentUser

CMS 用のログイン中ユーザーを表すサービスを追加する。

持たせる情報の例:

- ユーザーID
- ロール
- 表示名
- ログイン状態
- `isAuthenticated()` のような補助メソッド

これを追加した後は、各 Controller が直接 `Session` を読んで `cms.user_id` を解釈する状態を減らしていく。

### Permission

権限名は画面名ではなく、操作名で定義する。

例:

```text
article.list
article.create
article.update
article.delete
article.publish
article.restore
page.update
media.upload
media.delete
setting.update
taxonomy.manage
inquiry.read
```

画面単位で `article_screen` のように作ると、後で「一覧は見られるが削除はできない」のような制御がしにくくなる。

### AuthorizationService / Policy

認可判定には中央の入口を用意する。

例:

```php
$authorization->can($user, 'article.update');
$authorization->authorize($user, 'article.update');
```

対象リソースに依存する判定は Policy 的に分ける。

例:

```php
$articlePolicy->canUpdate($user, $article);
$articlePolicy->canPublish($user, $article);
```

ルート単位の権限チェックだけでは、特定の記事、特定の状態、特定の所有者に応じた判定までは表現しきれない。

### Middleware

Router に middleware 相当の route metadata を持たせると、CMS の認証・認可を差し込みやすい。

例:

```php
[
    'method' => 'POST',
    'path' => '/admin/articles/{id:\d+}',
    'handler' => ArticleUpdateController::class,
    'middleware' => ['cms.auth', 'cms.can:article.update'],
]
```

最初に入れるべき middleware は `cms.auth`。

現在 Controller に散らばっている次のような処理を middleware 側に寄せる。

```php
if (!$this->session->has('cms.user_id')) {
    return Response::redirect('/admin/login');
}
```

その後、`cms.can:*` のような route-level permission check を追加する。

### Template Authorization Helper

管理画面の表示制御には template helper を用意する。

例:

```latte
{if $can('article.delete')}
  ...
{/if}
```

ただし、これは表示制御に過ぎない。強制力のある認可ではない。

UI でボタンを隠しても、直接 POST される可能性がある。そのため、サーバー側の強制チェックは必須。

### UseCase Authorization

重要な書き込み操作は、Application layer または操作直前でも認可する。

対象例:

- 記事保存
- 記事削除
- 記事復元
- 固定ページ保存
- メディア削除
- 設定更新
- カテゴリ/タグ更新・削除

これにより、UI を迂回した直接 POST でも拒否できる。

## 承認ワークフロー

承認ワークフローは、単なる権限チェックとは別の概念。

権限チェックが答えること:

```text
このユーザーはこの操作を実行できるか？
```

承認ワークフローが答えること:

```text
この操作は即時反映してよいか？
それとも承認待ちとして保存するべきか？
```

ルール例:

- ライターは下書き作成・編集ができる。
- ライターは直接公開できない。
- ライターは公開申請できる。
- 編集者は申請を承認・差し戻しできる。
- 管理者は直接公開できる。
- 公開済み記事の更新は、即時反映ではなく承認待ちにする。

## データモデルの方向性

ワークフローのルールが固まるまでは、DB テーブルを急いで追加しない。

将来的に必要になりそうなテーブル:

```text
roles
permissions
role_permissions
cms_users
approval_requests
approval_request_changes
audit_logs
```

小規模運用なら、最初はロールと権限をコード上の設定として持ってもよい。管理画面からロールを変更したい段階になってから DB 化すればよい。

## 監査ログ

監査ログは HTTP アクセスログではなく、業務イベントとして記録する。

記録対象の例:

```text
article.created
article.updated
article.submitted_for_approval
article.approved
article.rejected
article.published
article.deleted
page.updated
media.uploaded
media.deleted
setting.updated
login.succeeded
login.failed
authorization.denied
```

監査ログに含めたい情報:

- 実行ユーザーID
- 実行ユーザーのロール
- アクション
- リソース種別
- リソースID
- 結果
- request id
- IP address
- user agent
- 必要に応じた before / after
- metadata
- timestamp

監査イベントには `AuditLoggerInterface` を使う。業務監査が目的なら、通常のファイルログだけに頼らない方がよい。

## 推奨実装順序

1. CMS 用 `CurrentUser` を追加する。
2. Permission 名と role-to-permission の対応をコード上で定義する。
3. `AuthorizationService` を追加する。
4. routes に middleware metadata を持たせられるようにする。
5. `cms.auth` middleware を実装し、Controller の重複ログインチェックを減らす。
6. `cms.can:*` middleware を実装し、ルート単位の大まかな権限チェックを入れる。
7. Latte 用の `can()` helper を追加する。
8. 重要な書き込み UseCase に UseCase-level authorization を追加する。
9. 書き込み成功・認可拒否に対して監査ログを記録する。
10. ロールルールが安定してから承認ワークフローを設計する。

## 失敗しやすいパターン

- template でボタンを隠すだけにする。
- Controller だけで権限チェックする。
- middleware に業務ルールを詰め込みすぎる。
- 各 Controller が Session を直接読んで role を解釈する。
- 権限チェックとワークフロー状態遷移を混ぜる。
- ロール/承認ルールが固まる前に DB テーブルを作り込む。
- 直接 POST への対策を忘れる。
- 認可拒否の監査ログを忘れる。
- admin なら何でもできる、という前提を暗黙にする。

## 設計ルール

認可は二段階で強制する。

```text
Route middleware:
  このルートに入ってよいか？

UseCase / Policy:
  このユーザーが、この対象に、この業務操作を実行してよいか？
```

UI の表示判定はあってよいが、それは強制ではない。

## 現時点の推奨

今すぐフルの承認ワークフローを作らない。

安全な次の一手は以下。

```text
CurrentUser
AuthorizationService
cms.auth middleware
cms.can middleware
template can() helper
破壊的操作・書き込み操作への UseCase-level check
```

この土台が安定した後であれば、承認ワークフローを追加してもルールが散らばりにくい。
