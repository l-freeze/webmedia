# 設計判断記録

この文書は「なぜそうするのか」「なぜ今はやらないのか」を残す場所である。

実装手順、過去レビュー、会話ログ、将来構想だけの内容は残さない。将来の誤実装を防ぐために必要な判断だけを短く残す。

## View コンパイルキャッシュ

### 判断

View コンパイルキャッシュは標準では有効にしない。

classic / non-worker runtime で必要になった場合だけ、app container から `LatteTemplateRenderer` へ app 別の `cacheDirectory` を明示して有効化する。

### 理由

- 標準 runtime は FrankenPHP worker mode である。
- worker mode では、一度ロードされた template class が同一 PHP process 内に残るため、定常リクエストでディスク上の compiled view cache の効果は小さい。
- View コンパイルキャッシュは HTML response cache ではない。DB row、記事本文、設定、ログイン状態、CSRF token、権限判定結果はキャッシュ対象にしない。
- template 変更の反映は worker restart の問題として扱う。

### 採用しないこと

- `VIEW_CACHE=true` のような env switch は作らない。
- `autoRefresh=false` 相当の設定は標準では採用しない。
- container / config コンパイルキャッシュは現時点では採用しない。

### 有効化する場合

- cache directory は app 別に分ける。
  - `storage/cache/views/front`
  - `storage/cache/views/cms`
- source 変更検知は有効のままにする。
- HTML response cache、DB query cache、CDN cache とは別機能として扱う。

## Layout Global Provider の cache

### 判断

Front の `LayoutGlobalProvider` は、現時点では provider interface や cache decorator へ分けない。

### 理由

- layout global data は Front の Presentation 層が template 用に合成する表示都合の data である。
- Shared に layout cache service を置くと、Front 固有の責務が横断層へ漏れる。
- 現状は `globals()` と `loadGlobals()` を分けるだけで、cache 制御と data 組み立てを読み分けられる。

### 分離を検討する条件

- CMS 更新時の明示 invalidation が必要になる。
- cache key や TTL が複数になる。
- locale、theme、preview、device、user state で layout data が変わる。
- 同じ TTL cache pattern が他の provider / Query に増える。
- test が cache 制御と data 組み立てで読みにくくなる。

## Middleware 境界

### 判断

Middleware は HTTP request / response の入口制御と横断処理に限定する。

対象リソース依存の業務判断は UseCase / Policy / Domain へ寄せる。

### Middleware で扱ってよいこと

- ログイン済みか確認する。
- route metadata の `requires_permission` を確認する。
- CSRF token を検証する。
- IP address、user-agent、request id など HTTP metadata を event に載せる。
- CMS の状態変更 request の HTTP envelope を Audit として記録する。

### Middleware で扱わないこと

- route variables の `{id}` から記事を取得して所有者判定する。
- 記事 status を見て公開、復元、削除の可否を判定する。
- Repository / Query を呼び、resource-level policy を実装する。

### 理由

Middleware に対象リソース依存の判定を置くと、同じ UseCase を CLI、batch、将来の API から呼んだときに認可が抜ける。`writer は自分の記事だけ編集可` のようなルールは、route-level permission ではなく UseCase / Policy で判定する。

## Route metadata

### 判断

現時点では `RouteMetadataReader` や `MatchedRoute` value は作らない。

### 理由

- route metadata を読む箇所がまだ少ない。
- 先に抽象化すると、何のための概念かが半端に見える。
- 現行の route match 2回は理解しやすく、route 数も少ないため性能問題になっていない。

### 導入を検討する条件

- route metadata を読む middleware が増える。
- `Router::ROUTE_METADATA_ATTRIBUTE` 参照が複数箇所へ広がる。
- route 数が増え、match コストが実測で問題になる。
- route name、vars、metadata、controller をまとめた `MatchedRoute` が必要になる。

## Audit / SecurityLog

### 判断

Audit、SecurityLog、HTTP access log は分ける。

CMS の状態変更 HTTP request は `CmsAuditMiddleware` で HTTP envelope として記録する。業務上の before / after や resource id が必要なものは、UseCase / Controller から業務イベントとして明示する。

BusinessAudit という別ログ種別は作らず、既存の `AuditEvent` に集約する。区別は `action`、`resource_type`、`metadata` で表現する。

### 理由

- HTTP request と業務操作は一対一とは限らない。
- middleware は path と status code しか正確に知らない。
- 記事保存、復元、メディア削除、設定更新などは、業務イベントとして明示した方が後から説明しやすい。
- HTTP envelope と業務イベントはどちらも監査証跡であり、保存先や閲覧 UI を分けるほどの運用要件は現時点ではない。

### 採用しないこと

- 全 GET request を Audit に保存しない。
- CMS login 成功 / 失敗を Audit として保存しない。SecurityLog として扱う。
- request body 全体、password、CSRF token、cookie、session id を保存しない。
- middleware が path や body から resource id、before / after を推測しない。
- `NullAuditLogger` を標準にしない。未設定を黙って捨てると検知できない。
- `BusinessAuditLogger` のような別 logger を作らない。必要になるまでは `AuditEvent` に集約する。

## 公開前仕様

### 判断

`robots.txt`、`sitemap.xml`、OGP / Twitter Card、RSS / Atom は場当たり的に実装しない。公開前必須に近いが、先に仕様を決めてから着手する。

問い合わせフォームは当面積極運用しない。ただし外部入力口であるため、運用を開始しない場合でも最低限の rate limit / spam 対策 / 個人情報取得説明は残す。

### 理由

- SEO / SNS / 配信系は一度公開すると外部 crawler や reader に観測されるため、後から雑に変えると挙動が読みにくい。
- 問い合わせフォームは小規模でも spam と個人情報取得の入口になる。

### 採用しないこと

- 仕様未確定のまま OGP / sitemap / feed を仮実装しない。
- 問い合わせフォームを運用しないことを理由に、防御を完全に省略しない。

## Application Port

### 判断

Port は最初の選択肢ではなく、Application が外側の能力を本当に必要とし、他の配置では依存方向や責務が崩れる場合に使う。

詳細は `docs/application_ports.md` を正とする。

### 例

- password verification は Application が必要とする外側の暗号処理能力なので Port 化してよい。
- Logger は PSR-3 を shared / framework の横断基盤として使えば足りる間は、Application Port 化しない。
- Audit も通常は shared / framework / presentation 側の横断基盤でよい。業務成立条件として証跡記録が必要になった場合だけ、業務名を持つ Port を検討する。
