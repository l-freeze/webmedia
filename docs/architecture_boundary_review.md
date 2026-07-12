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

### 1. Application が現在時刻を直接生成している

対象:

- `apps/cms/src/Application/Auth/Login/Interactor.php`
- `apps/cms/src/Application/Auth/ResetLoginAttempt/Interactor.php`

ログイン試行制限と解除処理で `new DateTimeImmutable()` を Application 内で直接呼んでいる。

懸念:

- ログイン制限は「現在から10分間」という判断に依存するため、現在時刻が業務入力に近い。
- テストや再実行時に基準時刻を固定しにくい。
- `docs/application_ports.md` の Clock port 検討基準に該当する可能性がある。

現時点の判断:

- フレームワーク全体で使う汎用 Clock を先に導入しない。
- Login / ResetLoginAttempt の時刻境界をテストで固定する必要が出た時点で、CMS Auth 用の最小の Clock port を検討する。

### 2. AuditSummary metadata

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

### 3. Front Business のカテゴリ slug 固定

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

## 次の検証順

1. Login / ResetLoginAttempt のテスト性を確認し、Clock port が必要か判断する。
2. Audit metadata は現状維持し、上記の見直し条件に該当する変更が出た時に再検討する。
3. Front Business のカテゴリ scope は、POC から継続運用へ進めるか、site model を導入する時点で再設計する。

## 現時点の結論

明確な境界違反としてすぐ修正すべき項目は、現在このメモには残っていない。

現在時刻の生成はテスト性の観点から追加判断が必要である。Audit metadata は現状の用途と管理範囲では汎用配列を許容できるが、入力経路や読み取り箇所が増えた場合は typed な境界を再検討する。
