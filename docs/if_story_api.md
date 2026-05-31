# フロントを API 化する場合の方針

このドキュメントは、将来フロント画面を別ドメインの SPA / アプリに切り替え、現在の公開フロントを API として扱う場合の注意点と追加開発方針を整理する。

前提として、現在の `apps/front` は `Input` / `Interactor` / `Output` を UseCase 単位に分けており、Interactor は HTTP や Latte に依存しない。したがって、既存の Interactor を再利用し、HTML Controller とは別に JSON Controller を追加する方針で API 化できる。

## 1. 公開ウェブメディア API として扱う場合

公開ウェブメディアとして、記事一覧、記事詳細、カテゴリ、タグ、固定ページ、検索結果を返す API は、基本的に認証不要でよい。

理由は、これらの情報は現在の HTML 画面でも公開されており、ブラウザで表示できる時点で第三者が取得可能な情報だからである。API 化によって重要になるのは、認証よりも「非公開データを返さないこと」と「過剰アクセスに備えること」である。

### 1.1 公開 API で守るべきこと

- `published` の記事だけを返す。
- `published_at` が現在時刻以前の記事だけを返す。
- `published_ends_at` が未設定、または現在時刻より後の記事だけを返す。
- 下書き、非表示、ゴミ箱、公開前、公開終了済みの記事は返さない。
- CMS 側の管理情報、問い合わせ、下書き本文、履歴、ユーザー情報は公開 API に出さない。
- JSON の項目は、画面に必要なものだけに絞る。
- SQL や内部 ID の扱いを API 利用者に前提化しすぎない。

現在の公開判定は `apps/front/src/Infrastructure/Persistence/PdoArticleQuery.php` の `publicCondition()` に集約されているため、公開記事 API でも同じ Query 実装を使えば、HTML 画面と同じ公開条件を維持できる。

### 1.2 CORS とアクセス制御の考え方

公開データ API は認証不要でよいが、別ドメインのフロントから呼ぶ場合は CORS を設定する。

- `Access-Control-Allow-Origin` は自分のフロントドメインだけに絞る。
- `*` は避ける。
- 認証なしの公開 API では Cookie を送らない前提にする。
- `OPTIONS` リクエストに対応する必要がある場合は Router / Controller 側で扱う。

ただし、CORS はブラウザ上の別ドメイン JavaScript からの読み取りを制限する仕組みであり、curl やサーバーサイドからの直接アクセスを防ぐものではない。第三者に絶対に取得されたくないデータは、公開 API で返してはならない。

### 1.3 過剰アクセス対策

公開 API は誰でも叩ける前提になるため、次の対策を検討する。

- CDN または HTTP cache を使う。
- 一覧 API はページングを必須にする。
- 1ページあたりの件数に上限を設ける。
- IP 単位、または CDN / reverse proxy 側で rate limit を設定する。
- 検索 API は無制限に重い検索を許可しない。
- エラー時にも詳細な内部情報を返さない。

公開記事、カテゴリ、タグ、固定ページはキャッシュしやすい。CMS で記事を更新したときにキャッシュを短時間で自然失効させるか、将来的に purge できる構成にする。

### 1.4 ソースの追加改修方針

既存の HTML Controller を JSON に置き換えるのではなく、HTML Controller と JSON Controller を分ける。

推奨する追加構造:

```text
apps/front/src/Infrastructure/Http/Controller/Api/
  HomeApiController.php
  ArticleApiController.php
  CategoryApiController.php
  TagApiController.php
  PageApiController.php
  SearchApiController.php
```

ルートは既存の画面 URL と衝突しないように `/api` 配下へ追加する。

```php
return [
    ['GET', '/api/articles', [ArticleApiController::class, 'index']],
    ['GET', '/api/articles/{slug}', [ArticleApiController::class, 'show']],
    ['GET', '/api/categories', [CategoryApiController::class, 'index']],
    ['GET', '/api/categories/{slug}', [CategoryApiController::class, 'show']],
    ['GET', '/api/tags', [TagApiController::class, 'index']],
    ['GET', '/api/tags/{slug}', [TagApiController::class, 'show']],
    ['GET', '/api/pages/{slug}', PageApiController::class],
    ['GET', '/api/search', SearchApiController::class],
];
```

Controller は既存の Interactor を呼び、`Output` を JSON 用の配列に変換して返す。

```php
$output = $this->interactor->execute(new Input((int) $request->input('page', '1')));

return Response::json([
    'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
    'pagination' => $output->pagination->toArray(),
]);
```

`src/Framework/Http/Response.php` には JSON 用 factory を追加するとよい。

```php
public static function json(array $data, int $status = 200): self
{
    return new self(
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        $status,
        ['Content-Type' => 'application/json; charset=utf-8'],
    );
}
```

エラー形式も HTML と分け、API では JSON に統一する。

```json
{
  "error": {
    "code": "not_found",
    "message": "記事が見つかりません。"
  }
}
```

### 1.5 Application 層の扱い

原則として、既存の Interactor / Output はそのまま使う。

- HTML 表示用の文言、見出し、テンプレート名は Controller 側に置く。
- JSON のキー名や HTTP status は API Controller 側に置く。
- Interactor は「どのデータを取得するか」「どういう業務条件で返すか」までを担当する。
- `Output` は UseCase の結果を表すオブジェクトとして維持する。

もし API と HTML で必要なデータが大きく違う場合は、無理に既存 UseCase を肥大化させず、`Application/Article/List` などの新しい UseCase を追加する。

## 2. お気に入りなど会員ページにする場合

お気に入り、閲覧履歴、会員限定記事、マイページのようなユーザーごとの機能を追加する場合は、公開 API とは別に認証・認可の仕組みが必要になる。

公開記事 API は認証不要でよいが、会員ページ API はログイン済みユーザーを識別し、そのユーザーが操作できるデータだけを返す必要がある。

### 2.1 追加する主な DB テーブル

例:

```text
members
  id
  name
  email
  password_hash
  status
  created_at
  updated_at

member_refresh_tokens
  id
  member_id
  token_hash
  expires_at
  revoked_at
  created_at

member_favorites
  member_id
  article_id
  created_at
```

機能によっては次のテーブルも追加する。

- `member_profiles`
- `member_password_resets`
- `member_email_verifications`
- `member_read_histories`
- `member_roles`
- `member_permissions`

CMS 管理者の `users` とフロント会員の `members` は分ける方針が望ましい。CMS の認証は管理者向け、front の会員認証は読者向けであり、責務と権限が異なるためである。

### 2.2 追加する認証 API

例:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/refresh
GET  /api/auth/me
```

パスワードログインを採用する場合は、パスワードを平文保存せず `password_hash()` で保存し、検証は `password_verify()` で行う。

別ドメインフロントでは、認証方式を最初に決める必要がある。

- Cookie + Session
- HttpOnly Cookie + CSRF 対策
- Access Token + Refresh Token

ブラウザアプリで扱う場合、長寿命トークンを localStorage に置く設計は避ける。Cookie を使う場合は `HttpOnly`、`Secure`、`SameSite`、CSRF 対策をセットで考える。

### 2.3 追加する認可

認証は「誰か」を確認する処理、認可は「何をしてよいか」を確認する処理である。

会員機能では、少なくとも次をチェックする。

- お気に入り一覧は本人のものだけ返す。
- お気に入り追加/削除は本人の `member_id` でだけ行う。
- 会員限定記事はログイン済み、かつ閲覧権限のある会員だけ返す。
- 退会済み、停止中の会員は API を使えないようにする。

実装上は `src/Framework` に API 用の認証 Guard または middleware 相当の仕組みを追加し、Controller の前段で認証済み会員を取り出せるようにする。

### 2.4 Application / Domain の追加方針

front 会員機能は `apps/front` 側の機能として追加する。

例:

```text
apps/front/src/Application/Auth/
  Login/
    Input.php
    Interactor.php
    Output.php
  Logout/
  Me/
  Refresh/

apps/front/src/Application/Favorite/
  List/
    Input.php
    Interactor.php
    Output.php
    Query/
      IFavoriteQuery.php
    Value/
      FavoriteArticle.php
  Add/
    Input.php
    Interactor.php
    Output.php
  Remove/
    Input.php
    Interactor.php
    Output.php

apps/front/src/Domain/
  Entity/
    Member.php
  Repository/
    IMemberRepository.php
    IMemberTokenRepository.php
    IFavoriteRepository.php
  Value/
    MemberId.php
    MemberEmail.php
    MemberStatus.php
```

Infrastructure には PDO 実装を置く。

```text
apps/front/src/Infrastructure/Persistence/
  PdoMemberRepository.php
  PdoMemberTokenRepository.php
  PdoFavoriteRepository.php
  PdoFavoriteQuery.php
```

Controller は `apps/front/src/Infrastructure/Http/Controller/Api` 配下に置き、HTML Controller と混ぜない。

### 2.5 お気に入り API の例

```text
GET    /api/me/favorites
POST   /api/me/favorites/{articleId}
DELETE /api/me/favorites/{articleId}
```

この API は認証必須にする。

`Favorite/List/Interactor` はログイン中の `memberId` を `Input` で受け取り、その会員のお気に入り記事だけを返す。

```php
$output = $this->interactor->execute(new Input($authenticatedMember->id));
```

`Favorite/Add/Interactor` は `memberId` と `articleId` を受け取り、対象記事が公開中であることを確認してからお気に入りに追加する。

注意点:

- Controller で受け取った `member_id` をリクエスト body から信頼しない。
- `member_id` は必ず認証済みセッションまたはトークンから取得する。
- 同じ記事のお気に入り重複を DB unique 制約で防ぐ。
- 存在しない記事、非公開記事、公開終了記事は追加できないようにする。

### 2.6 公開 API と会員 API の境界

公開 API:

- 認証不要。
- 公開済みコンテンツだけ返す。
- CORS、cache、rate limit を設定する。
- 例: `/api/articles`, `/api/articles/{slug}`, `/api/categories`, `/api/search`

会員 API:

- 認証必須。
- ユーザー本人の情報だけ返す。
- cache は原則 private または no-store にする。
- CSRF / token 更新 / logout / 失効処理を設計する。
- 例: `/api/auth/me`, `/api/me/favorites`, `/api/me/history`

この境界を曖昧にしないことが重要である。公開メディアとしての API は現在の Interactor / Output 構造を活かして比較的容易に追加できるが、会員機能は認証、DB、認可、セキュリティヘッダ、テストを含む別の開発項目として扱う。

### 2.7 追加テスト

公開 API 追加時:

- 公開記事だけ返ること。
- 下書き、非表示、公開前、公開終了済みの記事が返らないこと。
- 404 が JSON 形式で返ること。
- ページングの上限が効くこと。

会員 API 追加時:

- 未ログインでは 401 になること。
- 他人のお気に入りを取得できないこと。
- 非公開記事をお気に入りに追加できないこと。
- logout 後の token / session が使えないこと。
- 停止中会員が API を使えないこと。
