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

状態: 対応済み。

対象:

- `apps/cms/src/Domain/Entity/Article.php`
- `apps/cms/src/Domain/Entity/Page.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticleRepository.php`
- `apps/cms/src/Infrastructure/Persistence/PdoPageRepository.php`

対応前は `Article::fromInput()` と `Page::fromInput()` が `array<string, mixed>` を受け取り、`title`、`body_format`、`published_ends_at` などの配列キーを Domain Entity 内で読んでいた。

さらに Repository 側の DB row 復元でも `fromInput()` が使われていた。これは、Repository interface が Domain 型を受け取るようになった後も、shape 変換責務が Domain Entity に残っている状態だった。

問題だった点:

- Domain Entity がフォーム入力項目名を知っている。
- DB row 復元と HTTP input からの生成が同じ入口に混ざっている。
- `fromInput` という名前なのに、Repository の row 復元にも使われている。
- 今後項目が増えた時に、どの層が変換責務を持つべきか曖昧になる。

対応:

- Domain Entity から `fromInput(array)` を削除した。
- Entity 内の `stringValue` / `intValue` / `boolValue` / `nullablePositiveInt` のような shape helper を削除した。
- UseCase 側は validation 済み input から ValueObject を明示的に作る。
- Repository 側は DB row から ValueObject を明示的に作って `new Article(...)` / `new Page(...)` で復元する。
- 薄い `restore` / `reconstruct` wrapper は増やさず、DB 復元であることは Repository 内の private method 名と処理位置で表現する。

### 2. migration に現行 schema ではない履歴が残っている

状態: 対応済み。

対象:

- `database/migrations/20260523000100_create_initial_schema.php`

対応前は、現行概念が `cms_login_attempts` であるにもかかわらず、migration では一度 `login_attempts` を作成し、後続 migration で `cms_login_attempts` に rename していた。

また、`page_versions` の unique index も初期 schema ではなく後続 migration で追加されていた。

問題だった点:

- fresh migrate 時に、現行として正しくない途中概念が migration 履歴に残る。
- 初期 schema が「現在の正しい schema」を表すという方針とズレる。
- まだ開発段階であれば、不要な schema 進化履歴を残す理由が弱い。

対応:

- 初期 schema で `cms_login_attempts` と `cms_login_attempt_resets` を現行 schema として直接作るようにした。
- 初期 schema の `page_versions` に `page_id, version_no` の unique index を直接定義した。
- 途中概念を作るだけの login attempt rename migration と、page version index の追加 migration を削除した。

### 3. Application が現在時刻を直接生成している

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

### 4. 記事 status label が Infrastructure の static helper にある

状態: 対応済み。

対象:

- `apps/cms/src/Infrastructure/Persistence/PdoArticleRepository.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticleQuery.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticlePreviewQuery.php`
- `apps/cms/src/Infrastructure/Persistence/PdoArticleVersionPreviewQuery.php`

対応前は、記事の表示用 status label が `PdoArticleRepository::statusLabel()` に置かれ、複数の Query 実装から参照されていた。

問題だった点:

- Repository 実装に表示ラベル判断が置かれている。
- `InquiryStatus::label()` は Domain 側にあるため、配置の一貫性が弱い。
- status、published_at、published_ends_at から表示状態を読む判断が Infrastructure static helper に集まっている。

対応:

- DB に保存する `status` は変えず、「公開予約」「公開終了」は現在時刻と公開期間から導く CMS の表示上の派生状態と判断した。
- Query 実装は Application Value へ `status`、`publishedAt`、`publishedEndsAt` の生値だけを詰める。
- 日本語ラベルへの変換は `Presentation/Presenter/Article/ArticleStatusPresenter` に置き、Controller が template params の `status_label` を組み立てる。
- `PdoArticleRepository::statusLabel()` と Application Value の `statusLabel` を削除した。
- CMS Infrastructure から Presentation への依存と、Presentation から CMS Persistence 実装への直接依存を Architecture Test で禁止した。

## 妥協として成立しそうな候補

### 5. AuditSummary metadata

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

### 6. Route metadata

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

### 7. Tiptap JSON validation の重複

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

### 8. Front Business のカテゴリ slug 固定

対象:

- `apps/front-business/src/Infrastructure/Persistence/BusinessCategoryScope.php`

`front-business` は固定カテゴリ slug のみを表示対象にしている。

現時点の判断:

- site model 未導入の段階で、business front の表示範囲を絞る手段としては妥協可能。
- `apps/front-business` 内に閉じており、Kernel や CMS へ混入していない。

検証時の注意:

- CMS 側でカテゴリ slug を変えると壊れる。
- site model / article publication / site scope を導入するなら、この固定 slug scope は消える候補になる。

### 9. DB を読まない PdoSiteSettingQuery（対応済み）

対象:

- `apps/front-business/src/Infrastructure/Configuration/FixedSiteSettingQuery.php`

DB を読まず固定値を返す実装を、`PdoSiteSettingQuery` から `FixedSiteSettingQuery` へ改名した。`Persistence` 配下からも外し、固定設定の Infrastructure 実装であることを示す `Infrastructure/Configuration` へ移した。

現時点の判断:

- CMS の `site_settings` に business front を依存せず、POC 用の固定設定として front-business 内に閉じる。
- 実装名と配置は、DB 永続化を行わない現状に合わせる。

検証時の注意:

- business front の設定を DB 管理したくなった時は、site model と合わせて再設計する。

## 次の検証順

優先順は次の通り。

1. migration を現行 schema に畳めるか。
2. 未使用 schema を削除できるか。
3. Login / ResetLoginAttempt に Clock 境界を入れるべきか。
4. 妥協候補 6-10 は、現時点維持でよいかを確認し、必要なら `docs/decisions.md` に判断だけ残す。

## 精査時点の結論

前回までに修正した `site_settings`、Repository interface、cross-app cache key まわりの明確な境界違反はかなり減っている。

Domain Entity に残っていた配列 shape の吸収口は解消した。一方で、migration に現行 schema ではない履歴が残っている点は、まだ構造上の弱さとして目立つ。

Audit metadata、route metadata、Tiptap JSON、BusinessCategoryScope は違和感はあるが、現時点では目的に対する妥協として説明可能である。無理に抽象化すると、かえって責務がぼやける可能性がある。
