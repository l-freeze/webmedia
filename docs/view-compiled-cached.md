# View コンパイルキャッシュ方針

## 結論

このシステムでは、View コンパイルキャッシュは採用する価値がある。

ただし採用するのは「テンプレートソースを PHP 等へコンパイルした結果を再利用する」範囲だけに限定する。DB から取得した記事、設定、ログイン状態、CSRF token、権限、問い合わせ内容などの request data / application data はキャッシュしない。

速度のために安全性、データ反映、運用の確実性を落とす設定は採用しない。具体的には、テンプレート変更検知を切る `setAutoRefresh(false)` 相当の設定は Framework の公開設定として持たない。

## 判断基準

このフレームワークは個人ブログだけでなく、超大量アクセスサイトや基幹システムでも使える品質を目指す。その前提では、キャッシュは「速くなるか」だけでなく、次の条件を満たす必要がある。

- データ更新が次リクエストへ自然に反映される。
- request 固有状態を request 外へ持ち越さない。
- template 更新時に古い画面が残り続けない。
- キャッシュ削除やデプロイ手順のミスで業務上の不整合を起こさない。
- 個人運用でも安全に扱え、大規模運用では明確に拡張できる。

View コンパイルキャッシュは、この条件を満たす範囲で使う。

## TemplateRenderer の責務

`App\Framework\View\TemplateRenderer`（`src/Framework/View/TemplateRenderer.php`）は次の interface だけを持つ。

```php
render(string $template, array $params = []): string
```

この interface は「Controller が view name と view data を渡し、HTML 文字列を受け取る」境界である。Framework core は Latte、Smarty、Twig、Blade などの template engine を決めない。

ただし、interface だけで安全性が自動的に保証されるわけではない。各 renderer 実装は次の実装規約を守る。

- キャッシュしてよいのは template source のコンパイル結果だけ。
- `$params` は render 呼び出しごとに評価する。
- global provider の結果も render 呼び出しごとに評価する。
- HTML response 全体、DB row、認証状態、CSRF token、権限判定結果は View コンパイルキャッシュへ入れない。

現行の Latte 実装では `apps/shared/src/View/Infrastructure/LatteTemplateRenderer.php` の `render()` が、毎回 `renderToString($path, array_merge($globalParams, $params))` を呼び出す。この構造を維持する限り、記事の追加・更新・削除など DB 内容の変化は次リクエストで反映される。

## 採用するもの

### 1. Template engine の標準コンパイルキャッシュ

Latte / Smarty / Twig / Blade のような template engine が持つ「template source を PHP 等へ変換した結果」のキャッシュは採用してよい。

これは PHP ファイルの opcache に近い性質であり、HTML 出力キャッシュではない。template logic の再構築コストを下げるだけで、view data は毎回渡される。

### 2. 変更検知ありの運用

開発環境、本番環境ともに、template source の変更検知は有効にする。

Latte では `autoRefresh` の既定値が true であり、template source や extension の変化を検知して再コンパイルする。この既定値をそのまま使う。

この方針なら、個人ブログでは cache clear 手順を意識せず安全に運用できる。大量アクセスサイトや基幹システムでも、デプロイ後に古い template が残り続けるリスクを避けられる。`autoRefresh=true` のまま十分速い状態を標準にする。

### 3. app 別の cache directory

View cache は他の cache と混ぜない。Front / CMS / Minimal など app ごとに分ける。

推奨例:

- `storage/cache/views/front`
- `storage/cache/views/cms`
- `storage/cache/views/minimal`

現行の `apps/front/config/container.php` と `apps/cms/config/container.php` は、app 別の View cache directory を使う。

## 標準採用しないもの

### `autoRefresh=false`

Latte の `setAutoRefresh(false)` 相当の設定は採用しない。Framework の設定項目としても露出しない。

理由は明確で、template 変更時に cache clear を忘れると古い画面が出続けるからである。これは速度と引き換えに安全性と運用確実性を落とす選択であり、このシステムの標準方針には合わない。

mtime / source signature の確認コストは、通常のアプリケーションでは DB、Markdown 変換、HTML 生成、network、ブラウザ描画などに比べて小さい。ここを削って得られる速度より、古い template が残るリスクの方が重い。Framework の売りは「変更検知を切れば速い」ではなく、「変更検知を有効にしたまま速い」である。

専任のデプロイパイプラインで View cache の warmup と clear を完全に自動化できる環境でも、この Framework の標準設定として `autoRefresh=false` を用意する必要はない。必要なら利用者が独自の renderer 実装で明示的に行えばよいが、Framework 側はその最適化を推奨しない。

### Laravel / Symfony との違い

Laravel は Blade view を request 時に必要に応じてコンパイルし、未コンパイル view が compiled view より新しければ再コンパイルする。さらに本番向けには `view:cache` で事前コンパイルする手段を持つ。

Symfony / Twig は `cache` と `auto_reload` を分けており、`auto_reload` を指定しない場合は `debug` の値に応じて決まる。production で `auto_reload=false` に寄る思想は、「本番では template を直接編集せず、デプロイ成果物を immutable に扱い、cache warmup / clear をデプロイ手順へ組み込む」という前提に立つ。

この前提は、大規模な CI/CD と immutable deployment が徹底されている環境では成立する。ただし、このフレームワークでは採用しない。個人ブログ、超大量アクセスサイト、基幹システムのどれでも安全に使える default にするなら、template source の変更検知は有効にしておく方が堅い。

つまり Laravel / Symfony が間違っているというより、production optimization をデプロイ規律へ寄せる設計である。このフレームワークでは、標準設定を運用規律に依存させすぎない。default は `autoRefresh=true` 相当で固定し、設定スイッチとしては持たない。

### HTML response 丸ごとのキャッシュ

HTML 出力全体のキャッシュは View コンパイルキャッシュではない。

記事詳細やトップページの response cache は、大量アクセス対策として別途検討する価値がある。ただし、その場合は次を個別に設計する必要がある。

- 公開・非公開・公開予約・公開終了の invalidation
- ログイン状態、CSRF token、flash message、権限表示との分離
- 管理画面と公開画面の完全分離
- purge / tag / TTL / stale-while-revalidate の方針

したがって、本書の View コンパイルキャッシュとは別機能として扱う。

### container / config コンパイルキャッシュ

container / config のコンパイルキャッシュは現時点では採用しない。

理由は、request scope と worker lifetime scope の境界を複雑にし、誤った singleton 化や request 状態の持ち越し事故を起こしやすくするためである。このフレームワークでは per-request container 再生成と `workerSingleton()` の明示的な使い分けを重視する。

大量アクセス対策として将来検討する場合も、まず request scope の自動検査、Architecture Test、worker mode の状態漏れテストを揃えてから別作業として扱う。

## engine 別の扱い

| Engine | 採用してよい cache | 変更検知 | 標準方針 |
|---|---|---|---|
| Latte | `Engine::setCacheDirectory()` | `autoRefresh=true` | 採用、変更不可 |
| Smarty | compile directory | compile check 有効 | 採用可 |
| Twig | `Environment(['cache' => dir])` | `auto_reload=true` 相当 | 採用可 |
| Blade | compiled views | mtime 再コンパイル | 採用可 |
| 素の PHP | コンパイル概念なし | OPcache に委ねる | View cache 不要 |

Latte の `setTempDirectory()` は現行 vendor では deprecated なので、新規実装や修正では `setCacheDirectory()` を使う。

## 実装済みの作業

現時点の Latte 実装は既に app 別の cache directory を設定しているため、View コンパイルキャッシュ自体は有効である。

実装済みの範囲は次の通り。

1. `LatteTemplateRenderer` の `setTempDirectory()` を `setCacheDirectory()` へ置き換え済み。
2. Front / CMS の cache directory を `storage/cache/views/front` と `storage/cache/views/cms` へ分離済み。
3. tests で「同じ template でも `$params` を変えれば出力が変わる」ことを renderer の契約として固定済み。
4. docs に、View cache は HTML response cache ではないことを明記済み。

`setAutoRefresh(false)` はこの作業に含めない。環境変数や config で切り替える仕組みも作らない。

### 実装時の固定方針

Latte renderer 側で `autoRefresh` の扱いを明示する場合は、env や app config で切り替えられる設定にはしない。

Framework 標準実装では、renderer 内の private constant で固定する。

```php
private const AUTO_REFRESH = true;
```

この constant は利用者向けの設定値ではなく、Framework の設計判断をコード上に残すためのものとして扱う。標準値は必ず true にする。

コメントを残す場合は、次の趣旨にする。

- true のままでも View コンパイルキャッシュの主要な性能メリットは得られる。
- false で削れるのは template source 変更検知のコストだけである。
- false は古い template が残るリスクを持つため、Framework 標準では推奨しない。
- Latte と worker の挙動を理解しており、リスクなくパフォーマンス向上ができることを開発者自身が保証できる場合のみ、独自 renderer での変更を検討する。
- このシステムは `autoRefresh=true` のまま高い throughput を出せることを前提に設計する。

つまり、Framework 標準は「設定で速さと安全性を選ばせる」のではなく、「安全な設定のまま速い」を実装として固定する。

## 用途別評価

### 個人ブログ

採用するメリットはある。初回 render 後の template compile cost を避けられ、運用負担は増えない。

ただし安全性を保つため、変更検知は有効のままにする。個人運用では cache clear 忘れを前提に設計した方がよい。

### 超大量アクセスサイト

View コンパイルキャッシュは最低限の土台として有効だが、それだけで大量アクセス対策になるわけではない。

本当に効くのは CDN、reverse proxy、response cache、DB query cache、read replica、静的 asset 配信、worker mode、OPcache などとの組み合わせである。View コンパイルキャッシュはその中で安全に常時有効化できる低リスクな最適化として扱う。

### 基幹システム

採用してよい。ただし、基幹システムでは「速いが古い画面が出る可能性がある」設定は許容しない。

そのため標準は変更検知あり、app 別 cache directory、HTML response cache との分離で固定する。権限、CSRF、監査、承認状態など request / user / workflow に紐づく値を View cache に入れない限り、安全性は維持できる。

## 最終方針

このシステムで実装するメリットはある。ただしメリットの中心は「安全な標準最適化として template compile cost を避けること」であり、「運用リスクを取って限界まで速くすること」ではない。目指す状態は、変更検知を有効にしたまま高速な View rendering である。

したがって、採用方針は次の通り。

- View コンパイルキャッシュは採用する。
- data cache / response cache とは明確に分ける。
- `autoRefresh=true` 相当で固定する。
- `autoRefresh=false` の設定スイッチは作らない。
- cache directory は app 別に分離する。
- container / config コンパイルキャッシュは現時点では採用しない。
