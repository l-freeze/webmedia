# アーキテクチャ境界精査メモ

## 目的

この文書は、責務、境界、構造を無視する作りが残っていないかを精査し、今後も判断が必要な項目だけを残すメモである。対応済みの項目はこの文書から削除し、対応履歴は Git 履歴と `docs/current_status.md` に任せる。

主に次のような作りを検査対象とする。

- 別アプリケーションの都合を直接知る依存。
- Application / Domain が Infrastructure、HTTP、DB、実行環境など外側の詳細を知る依存。
- Repository や Entity がフォーム入力 shape、DB row shape、汎用配列を吸収する作り。
- typed table にするべきデータを key-value / metadata / EAV 風の何でも箱に逃がす作り。
- Framework / Kernel にアプリケーション固有の知識が混ざる作り。

## 要判断候補

### 1. AuditSummary metadata

対象:

- `apps/cms/src/Presentation/Audit/AuditSummary.php`
- `apps/cms/src/Presentation/Audit/AuditReporter.php`
- `apps/cms/src/Presentation/Http/Middleware/AuditMiddleware.php`
- `apps/shared/src/Audit/Domain/AuditEvent.php`

#### metadata が何を表すか

Audit の `metadata` は、テーブルの任意属性を保存する EAV ではなく、監査イベントごとに異なる補足情報を持つ JSON 相当の payload である。`action`、`resourceType`、`resourceId`、`result`、actor などの共通項目は metadata の外に typed property として持っている。

例えば記事更新では、概ね次のようなイベントになる。

```php
[
    'action' => 'article.updated',
    'resource_type' => 'article',
    'resource_id' => '42',
    'result' => 'success',
    'metadata' => [
        'method' => 'POST',
        'path' => '/admin/articles/42',
        'status_code' => 302,
        'title' => '更新後のタイトル',
        'slug' => 'updated-slug',
        'status' => 'published',
        'body_format' => 'rich_text',
    ],
]
```

問い合わせ更新では管理メモ本文を保存せず、`admin_note_present`、`admin_note_length`、`admin_note_hash` だけを残す。管理者 password も値そのものではなく `password_changed` という bool だけを残す。

#### 処理経路

1. Controller が状態変更成功後に `AuditReporter::articleSaved()` などの用途別 method を呼ぶ。
2. `AuditReporter` が用途ごとに許可された項目だけを `AuditSummary` に入れる。
3. `AuditMiddleware` が HTTP method、path、status code、actor、IP address などと結合し、最終的な `AuditEvent` を一度だけ dispatch する。

#### なぜ汎用配列を許容しているか

- `article.updated`、`media.uploaded`、`security.login_attempt_reset` で必要な補足項目が異なる。
- 全 action の全項目を nullable property として持つ巨大 DTO や typed table にすると、監査対象を追加するたびに構造が膨らむ。
- 現在は `AuditReporter` の用途別 method に記録項目を閉じており、任意の request body 全体を保存する作りにはなっていない。

現時点の判断:

- metadata の存在自体は問題としない。監査ログの可変 payload として現状維持する。
- password、token、cookie、session id、CSRF token、本文全文、過度な個人情報は入れない。

見直し条件:

- Controller が用途別 method ではなく、public `describe()` に任意配列を渡す例が増える。
- 同じ action の metadata shape が出力箇所ごとに異なる。
- Audit の検索・集計要件により、metadata 内の特定項目に型、index、制約が必要になる。
- 記録禁止値の混入をテストとレビューで防げなくなる。

### 2. Front Business のカテゴリ slug 固定

対象:

- `apps/front-business/src/Infrastructure/Persistence/BusinessCategoryScope.php`

`front-business` は固定カテゴリ slug のみを表示対象にしている。

現時点の判断:

- front-business は POC であり、site model 未導入の段階で表示範囲を絞る方法として許容する。
- `apps/front-business` 内に閉じており、Kernel、CMS、通常 Front の責務には混ぜない。
- 現在は修正対象にしない。

見直し条件:

- CMS 側で対象カテゴリ slug を変更する必要が出る。
- front-business を POC ではなく継続運用する。
- site model / article publication / site scope を導入する。

### 3. array_map / array_filter 使用箇所

`docs/webmedia_architecture_rules.md` の配列処理ルールに対し、既存実装を後から用途別に検証するための一覧である。2026-07-15 時点の PHP 実装を対象とし、POC の `apps/front-business`、未追跡の `apps/minimal`、tests、vendor は対象外とする。

件数:

- `array_map`: 33 呼び出し、24 ファイル。
- `array_filter`: 16 呼び出し、16 ファイル。
- `array_reduce`: 0 呼び出し。
- `array_walk`: 0 呼び出し。

#### array_map

- `database/seeds/InitialDataSeeder.php:67`
- `apps/cms/config/container.php:97`
- `apps/cms/src/Infrastructure/Persistence/PdoMediaQuery.php:76`
- `apps/cms/src/Infrastructure/Persistence/PdoMediaQuery.php:88`
- `apps/cms/src/Infrastructure/Persistence/PdoMediaQuery.php:100`
- `apps/cms/src/Infrastructure/Persistence/PdoMediaQuery.php:112`
- `apps/cms/src/Presentation/Http/Controller/AdminUser/List/Controller.php:40`
- `apps/cms/src/Presentation/Http/Controller/Article/Create/Controller.php:53`
- `apps/cms/src/Presentation/Http/Controller/Article/Create/Controller.php:54`
- `apps/cms/src/Presentation/Http/Controller/Article/Edit/Controller.php:52`
- `apps/cms/src/Presentation/Http/Controller/Article/Edit/Controller.php:62`
- `apps/cms/src/Presentation/Http/Controller/Article/Edit/Controller.php:63`
- `apps/cms/src/Presentation/Http/Controller/Article/History/Controller.php:29`
- `apps/cms/src/Presentation/Http/Controller/Article/List/Controller.php:35`
- `apps/cms/src/Presentation/Http/Controller/Media/List/Controller.php:33`
- `apps/cms/src/Presentation/Http/Controller/Page/Create/Controller.php:53`
- `apps/cms/src/Presentation/Http/Controller/Page/Edit/Controller.php:50`
- `apps/cms/src/Presentation/Http/Controller/Page/Edit/Controller.php:60`
- `apps/cms/src/Presentation/Http/Controller/Page/List/Controller.php:30`
- `apps/cms/src/Presentation/Http/Controller/Setting/Edit/Controller.php:44`
- `apps/cms/src/Presentation/Http/Controller/Trash/List/Controller.php:30`
- `apps/front/config/container.php:62`
- `apps/front/src/Presentation/Http/Controller/Article/Show/Controller.php:47`
- `apps/front/src/Presentation/Http/Controller/Category/List/Controller.php:30`
- `apps/front/src/Presentation/Http/Controller/Category/Show/Controller.php:36`
- `apps/front/src/Presentation/Http/Controller/Home/Show/Controller.php:27`
- `apps/front/src/Presentation/Http/Controller/Home/Show/Controller.php:28`
- `apps/front/src/Presentation/Http/Controller/Search/Index/Controller.php:38`
- `apps/front/src/Presentation/Http/Controller/Tag/List/Controller.php:30`
- `apps/front/src/Presentation/Http/Controller/Tag/Show/Controller.php:35`
- `apps/front/src/Presentation/View/LayoutGlobalProvider.php:56`
- `apps/front/src/Presentation/View/LayoutGlobalProvider.php:60`
- `apps/shared/config/container.php:44`

#### array_filter

- `apps/cms/config/container.php:97`
- `apps/cms/src/Application/AdminUser/Create/Interactor.php:62`
- `apps/cms/src/Application/AdminUser/Update/Interactor.php:76`
- `apps/cms/src/Application/Article/Save/InputValidator.php:19`
- `apps/cms/src/Application/Category/Save/Interactor.php:21`
- `apps/cms/src/Application/Category/Update/Interactor.php:21`
- `apps/cms/src/Application/Page/Save/InputValidator.php:18`
- `apps/cms/src/Application/Tag/Save/Interactor.php:21`
- `apps/cms/src/Application/Tag/Update/Interactor.php:21`
- `apps/cms/src/Presentation/Audit/AuditReporter.php:173`
- `apps/cms/src/Presentation/Http/Controller/Audit/List/Controller.php:57`
- `apps/cms/src/Presentation/Http/Controller/Setting/Edit/Controller.php:43`
- `apps/front/config/container.php:62`
- `apps/front/src/Application/Inquiry/Create/Interactor.php:39`
- `apps/front/src/Infrastructure/Persistence/Sitemap/PdoSitemapEntryQuery.php:98`
- `apps/shared/config/container.php:44`

現時点では一覧化だけを行い、各使用箇所を `foreach` へ変更するか、配列処理ルール側に例外を設けるかは判断しない。

### 4. Query / Repository の戻り値境界

2026-07-15 時点で、POC の `apps/front-business`、未追跡の `apps/minimal`、tests、vendor を除き、Query interface 41個・45 method、Repository interface 12個・29 method とその実装を確認した。

件数の `int`、真偽判定の `bool`、戻り値を持たない `void` は規約どおり対象外とする。Query の UseCase Value、UseCase Value の型付き配列、Repository の Domain Entity / Value は適合として扱う。

変更対象は次の2件である。

#### AdminUser Repository の生成 ID

- `apps/cms/src/Domain/Repository/IAdminUserRepository.php:12`
  - `create(AdminUser $user): int` が生成 ID を primitive のまま返している。
- `apps/cms/src/Infrastructure/Persistence/PdoAdminUserRepository.php:46`
  - `lastInsertId()` を `int` へ変換して返している。
- `apps/cms/src/Application/AdminUser/Create/Interactor.php:55`
  - Repository から返された `int` をそのまま Output へ渡している。
- `tests/Cms/AdminUser/AdminUserInteractorTest.php:99`
  - in-memory Repository も `int` を返している。

Domain Value の `AdminUserId` を導入し、Repository interface と実装の戻り値を揃える。Application Output / Presentation では、必要な外側の境界で scalar へ変換する。

#### Role List Query の permission code 配列

- `apps/cms/src/Application/Role/List/Query/IRolePermissionQuery.php:10`
  - `permissionsForRole()` が `list<string>` を返している。
- `apps/cms/src/Infrastructure/Auth/Authorization/CodeDefinedRolePermissionQuery.php:93`
  - 定義済み permission code の primitive 配列を返している。
- `apps/cms/src/Application/Role/List/Interactor.php:21`
  - primitive 配列を受け取り、`Role` Value へ渡している。
- `apps/cms/src/Application/Role/List/Value/Role.php:9`
  - Query 由来の permission code を `list<string>` として保持している。
- `tests/Cms/Role/RoleListInteractorTest.php:15`
  - Query の戻り値と `Role` Value の型変更に合わせた検証が必要になる。

Role List UseCase 配下に permission code を表す Value を置き、Query はその Value の型付き配列を返す。認可判定用の `hasPermission(): bool` は真偽判定の例外なので変更対象にしない。

上記以外の Query / Repository 戻り値に、今回の規約に反する scalar、DB row、連想配列は確認されなかった。

## 次の検証順

1. Audit metadata は現状維持し、上記の見直し条件に該当する変更が出た時に再検討する。
2. Front Business のカテゴリ scope は、POC から継続運用へ進めるか、site model を導入する時点で再設計する。
3. `array_map` / `array_filter` は上記一覧を用途別に確認し、副作用、可読性、型の崩れ、処理量を見て個別に判断する。
4. Query / Repository の戻り値境界は、AdminUser Repository、Role List Query の順に修正する。

## 現時点の結論

明確な戻り値境界の修正対象として、AdminUser Repository の生成 ID と Role List Query の permission code 配列が残っている。

Audit metadata は現状の用途と管理範囲では汎用配列を許容できるが、入力経路や読み取り箇所が増えた場合は typed な境界を再検討する。`array_map` / `array_filter` は未検証項目として使用箇所を記録済みである。
