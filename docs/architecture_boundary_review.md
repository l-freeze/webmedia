# アーキテクチャ境界精査メモ

## 目的

この文書は、責務、境界、構造を無視する作りが残っていないかを全体精査した結果を、後から忘れず処理するための抽出メモである。

今回の精査は、単なるコードスタイル確認ではない。以下のような作りは、短期的には動いても、システムの理解、変更、安全性を壊すため、重点的に探した。

- 別アプリケーションの都合を直接知る依存。
- Application / Domain が Infrastructure、HTTP、DB、実行環境など外側の詳細を知る依存。
- Repository や Entity がフォーム入力 shape、DB row shape、汎用配列を吸収する作り。
- typed table にするべきデータを key-value / metadata / EAV 風の何でも箱に逃がす作り。
- Framework / Kernel にアプリケーション固有の知識が混ざる作り。
- 現行 schema として不要な履歴や将来用テーブルが残り、構造の正しさを曖昧にする作り。

抽出した候補は、すぐ直すべき問題と、合理的な妥協として成立している可能性が高いものに分ける。次のステップでは、各候補が本当に修正対象か、現在の規模と目的に対して妥協として成立しているかを検証する。

## 強めの候補

### 1. Domain Entity が input / row shape を吸収している

対象:

- `apps/cms/src/Domain/Entity/Article.php`
- `apps/cms/src/Domain/Entity/Page.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticleRepository.php`
- `apps/cms/src/Infrastructure/Persistence/PdoPageRepository.php`

`Article::fromInput()` と `Page::fromInput()` が `array<string, mixed>` を受け取り、`title`、`body_format`、`published_ends_at` などの配列キーを Domain Entity 内で読んでいる。

さらに Repository 側の DB row 復元でも `fromInput()` が使われている。これは、Repository interface が Domain 型を受け取るようになった後も、shape 変換責務が Domain Entity に残っている状態である。

懸念:

- Domain Entity がフォーム入力項目名を知っている。
- DB row 復元と HTTP input からの生成が同じ入口に混ざっている。
- `fromInput` という名前なのに、Repository の row 復元にも使われている。
- 今後項目が増えた時に、どの層が変換責務を持つべきか曖昧になる。

検証方針:

- Domain Entity は constructor と明示的な named arguments / ValueObject だけを受け取る形へ寄せられるか確認する。
- UseCase 側の入力変換と Infrastructure 側の row 復元を別々に表現する。
- ただし、薄い wrapper を増やすだけなら悪化するため、変換責務の置き場所を先に決める。

### 2. migration に現行 schema ではない履歴が残っている

対象:

- `database/migrations/20260703000100_create_login_attempts.php`
- `database/migrations/20260704000100_create_cms_login_attempt_resets.php`
- `database/migrations/20260524000300_add_unique_index_to_page_versions.php`

現行概念は `cms_login_attempts` だが、migration では一度 `login_attempts` を作成し、後続 migration で `cms_login_attempts` に rename している。

また、`page_versions` の unique index も初期 schema ではなく後続 migration で追加されている。

懸念:

- fresh migrate 時に、現行として正しくない途中概念が migration 履歴に残る。
- 初期 schema が「現在の正しい schema」を表すという方針とズレる。
- まだ開発段階であれば、不要な schema 進化履歴を残す理由が弱い。

検証方針:

- `cms_login_attempts` と `cms_login_attempt_resets` を現行 schema として直接作る。
- `page_versions` の unique index を初期 schema に畳む。
- 既存データ移行を重視する段階か、fresh schema の正しさを優先する段階かを確認する。

### 3. 未使用に見える schema が残っている

対象:

- `database/migrations/20260523000100_create_initial_schema.php`
  - `operation_logs`
  - `redirects`

検索範囲では、`operation_logs` と `redirects` を参照する production code が見つからなかった。

懸念:

- 将来使うかもしれないテーブルが現行 schema に混ざっている。
- 実装されていない責務が、DB schema 上だけ存在している。
- Audit / SecurityLog が整備された後に `operation_logs` が残ると、ログ責務が曖昧に見える。

検証方針:

- 現行機能として使っていないなら初期 schema から外す。
- redirect 機能を本当に作るなら、Front / CMS / site model のどこに所属する機能かを先に決める。
- operation log は Audit / SecurityLog と責務が重複しないか確認する。

### 4. Application が現在時刻を直接生成している

対象:

- `apps/cms/src/Application/Auth/Login/Interactor.php`
- `apps/cms/src/Application/Auth/ResetLoginAttempt/Interactor.php`

ログイン試行制限と解除処理で `new DateTimeImmutable()` を Application 内で直接呼んでいる。

懸念:

- `docs/application_ports.md` では、Application が現在時刻を業務判断に使う場合は Clock port 候補としている。
- ログイン試行制限は時刻 window に依存するため、テストや再実行時の基準時刻が固定しづらい。

検証方針:

- Login / ResetLoginAttempt の時刻が業務判断として十分重要か確認する。
- Clock port を入れる価値があるか、現状の小ささを理由に妥協するか判断する。
- 広域 Clock を安易に入れず、必要なら CMS Auth 用の時刻境界として最小化する。

### 5. 記事 status label が Infrastructure の static helper にある

対象:

- `apps/cms/src/Infrastructure/Persistence/PdoArticleRepository.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticleQuery.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticlePreviewQuery.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticleVersionPreviewQuery.php`

記事の表示用 status label が `PdoArticleRepository::statusLabel()` に置かれ、複数の Query 実装から参照されている。

懸念:

- Repository 実装に表示ラベル判断が置かれている。
- `InquiryStatus::label()` は Domain 側にあるため、配置の一貫性が弱い。
- status、published_at、published_ends_at から表示状態を読む判断が Infrastructure static helper に集まっている。

検証方針:

- Domain Value / Application Value / Presentation helper のどこに置くべきか判断する。
- 「公開予約」「公開終了」は表示上の派生状態か、業務状態の読み替えかを先に決める。
- Infrastructure static helper は避ける方向で検討する。

## 妥協として成立しそうな候補

### 6. AuditSummary metadata

対象:

- `apps/cms/src/Presentation/Audit/AuditSummary.php`
- `apps/cms/src/Presentation/Audit/AuditReporter.php`

`AuditSummary::$metadata` は `array<string, mixed>` であり、EAV 風に見える。

現時点の判断:

- Audit は action ごとに補足項目が異なる。
- 完全に typed table 化すると、監査対象が増えるたびに schema と UI が重くなる。
- 現状は `AuditReporter` がメソッドごとに項目を絞っているため、妥協として成立している。

検証時の注意:

- 問題にすべきなのは metadata の存在そのものではない。
- `AuditReporter` を経由せず、何でも metadata に入れられる経路が増えた場合に問題化する。
- password、token、本文全文、過度な個人情報を入れない境界を維持する。

### 7. Route metadata

対象:

- `src/Framework/Routing/Router.php`
- `apps/cms/config/routes.php`

route metadata は `array<string, mixed>` で、CMS では `requires_permission` を使っている。

現時点の判断:

- Framework の route 拡張点としては許容できる。
- CMS 側の用途は `requires_permission` に限定されている。
- `docs/decisions.md` にも、現時点では `RouteMetadataReader` や `MatchedRoute` value を作らない判断がある。

検証時の注意:

- metadata を読む middleware が増えた場合は typed metadata / route definition を検討する。
- `Router::ROUTE_METADATA_ATTRIBUTE` 参照が広がった場合は見直す。

### 8. Tiptap JSON validation の重複

対象:

- `apps/cms/src/Application/Article/Save/Interactor.php`
- `apps/cms/src/Application/Page/Save/Interactor.php`

Article / Page の Save UseCase に、rich_text body の JSON decode と空判定が重複している。

現時点の判断:

- rich_text の正本が Tiptap / ProseMirror JSON であることは `docs/decisions.md` と整合している。
- 重複はあるが、薄い ValueObject で包むだけなら見通しが悪化する。
- 現状の規模では、UseCase 内の小さな validation として妥協できる可能性が高い。

検証時の注意:

- 抽象化するなら「本文が空かどうかを判定する validator / inspector」として意味を持たせる。
- Domain Entity に外部 editor schema を持ち込みすぎない。
- renderer と validation の責務を混ぜない。

### 9. Front Business のカテゴリ slug 固定

対象:

- `apps/front-business/src/Infrastructure/Persistence/BusinessCategoryScope.php`

`front-business` は固定カテゴリ slug のみを表示対象にしている。

現時点の判断:

- site model 未導入の段階で、business front の表示範囲を絞る手段としては妥協可能。
- `apps/front-business` 内に閉じており、Kernel や CMS へ混入していない。

検証時の注意:

- CMS 側でカテゴリ slug を変えると壊れる。
- site model / article publication / site scope を導入するなら、この固定 slug scope は消える候補になる。

### 10. DB を読まない PdoSiteSettingQuery

対象:

- `apps/front-business/src/Infrastructure/Persistence/PdoSiteSettingQuery.php`

`PdoSiteSettingQuery` という名前だが、実際には DB を読まず固定値を返している。

現時点の判断:

- CMS の `site_settings` に business front を依存させないという意味では、境界としては悪くない。
- ただし `Pdo` という名前は実装事実とズレている。

検証時の注意:

- 修正するなら `FixedSiteSettingQuery` や `BusinessSiteSettingQuery` のように、実装内容に合う名前へ変える。
- business front の設定を DB 管理したくなった時は、site model と合わせて再設計する。

## 次の検証順

優先順は次の通り。

1. Domain Entity の `fromInput(array)` をどう扱うか。
2. migration を現行 schema に畳めるか。
3. 未使用 schema を削除できるか。
4. Login / ResetLoginAttempt に Clock 境界を入れるべきか。
5. 記事 status label の配置を見直すか。
6. 妥協候補 6-10 は、現時点維持でよいかを確認し、必要なら `docs/decisions.md` に判断だけ残す。

## 精査時点の結論

前回までに修正した `site_settings`、Repository interface、cross-app cache key まわりの明確な境界違反はかなり減っている。

一方で、配列 shape の吸収口が Domain Entity に残っている点と、migration に現行 schema ではない履歴が残っている点は、まだ構造上の弱さとして目立つ。

Audit metadata、route metadata、Tiptap JSON、BusinessCategoryScope は違和感はあるが、現時点では目的に対する妥協として説明可能である。無理に抽象化すると、かえって責務がぼやける可能性がある。
