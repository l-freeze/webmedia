# View コンパイルキャッシュ方針

## 結論

このシステムでは、View コンパイルキャッシュは標準では有効にしない。

理由は、標準 runtime が FrankenPHP worker mode であり、Latte は一度ロードした template class を同一 PHP process 内で再利用するためである。`vendor/latte/latte/src/Latte/Engine.php` の `loadTemplate()` は `class_exists($class, autoload: false)` を先に見るため、worker mode の定常リクエストではディスク上の compiled view cache へ到達しない。

View コンパイルキャッシュを有効にするのは、template class が request をまたいで残らない classic / non-worker runtime を使う場合に限定する。DB から取得した記事、設定、ログイン状態、CSRF token、権限、問い合わせ内容などの request data / application data は、引き続きキャッシュしない。

## 判断基準

このフレームワークは個人ブログだけでなく、超大量アクセスサイトや基幹システムでも使える品質を目指す。その前提では、キャッシュは「速くなるか」だけでなく、次の条件を満たす必要がある。

- データ更新が次リクエストへ自然に反映される。
- request 固有状態を request 外へ持ち越さない。
- template 更新時に古い画面が残り続けない。
- キャッシュ削除やデプロイ手順のミスで業務上の不整合を起こさない。
- 個人運用でも安全に扱え、大規模運用では明確に拡張できる。

worker mode では、template 変更を反映するには worker restart が必要である。これは View コンパイルキャッシュではなく worker lifecycle の問題として扱う。

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

Latte / Smarty / Twig / Blade のような template engine が持つ「template source を PHP 等へ変換した結果」のキャッシュは、classic / non-worker runtime では採用してよい。

これは PHP ファイルの opcache に近い性質であり、HTML 出力キャッシュではない。template logic の再構築コストを下げるだけで、view data は毎回渡される。

### 2. worker mode では標準無効

worker mode では、View コンパイルキャッシュは標準で無効にする。

Latte は template を一度ロードすると template class が PHP process 内に残る。以後は `class_exists($class, autoload: false)` で処理が終わるため、ディスク上の compiled view cache の有無は定常リクエストの性能にほぼ影響しない。

template source の変更も、worker 内に class がロード済みなら `autoRefresh` の再チェックへ進まない。開発・運用では worker restart によって反映する。

### 3. app 別の cache directory

classic / non-worker runtime で View cache を有効にする場合は、他の cache と混ぜない。Front / CMS / Minimal など app ごとに分ける。

推奨例:

- `storage/cache/views/front`
- `storage/cache/views/cms`
- `storage/cache/views/minimal`

現行の `apps/front/config/container.php` と `apps/cms/config/container.php` は、標準では View cache directory を渡さない。

### 4. classic / non-worker runtime の opt-in

classic / non-worker runtime で View コンパイルキャッシュを使う場合は、app container から `LatteTemplateRenderer` へ `cacheDirectory` を明示的に渡す。

```php
new LatteTemplateRenderer(
    templateDirectory: $root . '/apps/front/templates',
    cacheDirectory: $root . '/storage/cache/views/front',
);
```

この場合でも `autoRefresh=true` 相当で変更検知を有効にする。`setAutoRefresh(false)` は標準では採用しない。

## 標準採用しないもの

### View コンパイルキャッシュの有効化スイッチ

`VIEW_CACHE=true`、`CMS_VIEW_CACHE=true`、`FRONT_VIEW_CACHE=true` のような公開設定は採用しない。

理由は、runtime が worker か classic かで意味が変わる設定を env switch として隠すと、性能や変更反映の前提が読みづらくなるからである。

必要な場合は、app container の `new LatteTemplateRenderer(..., cacheDirectory: ...)` で明示する。

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
| Latte | `Engine::setCacheDirectory()` | `autoRefresh=true` | worker では標準無効、classic/non-worker では明示 opt-in |
| Smarty | compile directory | compile check 有効 | classic/non-worker では採用可 |
| Twig | `Environment(['cache' => dir])` | `auto_reload=true` 相当 | classic/non-worker では採用可 |
| Blade | compiled views | mtime 再コンパイル | classic/non-worker では採用可 |
| 素の PHP | コンパイル概念なし | OPcache に委ねる | View cache 不要 |

Latte の `setTempDirectory()` は現行 vendor では deprecated なので、新規実装や修正では `setCacheDirectory()` を使う。

## 実装済みの作業

現時点の Latte 実装は `cacheDirectory` を省略できるため、標準では View コンパイルキャッシュを無効にしている。

実装済みの範囲は次の通り。

1. `LatteTemplateRenderer` は `?string $cacheDirectory = null` を受け取り、null の場合は `Engine::setCacheDirectory()` を呼ばない。
2. Front / CMS の container は標準では cache directory を渡さない。
3. tests で「同じ template でも `$params` を変えれば出力が変わる」ことを renderer の契約として固定済み。
4. tests で、compiled view cache が標準無効であり、`cacheDirectory` 明示時だけ有効になることを固定済み。
5. docs に、View cache は HTML response cache ではないことを明記済み。

`setAutoRefresh(false)` はこの作業に含めない。環境変数や config で切り替える仕組みも作らない。

### 実装時の固定方針

Latte renderer 側で `autoRefresh` の扱いを明示する場合は、env や app config で切り替えられる設定にはしない。

Framework 標準実装では、cache directory が明示された場合だけ renderer 内の private constant を使う。

```php
private const AUTO_REFRESH = true;
```

この constant は利用者向けの設定値ではなく、classic / non-worker runtime で compiled view cache を明示的に有効化した場合も、変更検知を切らないという設計判断をコード上に残すためのものとして扱う。

コメントを残す場合は、次の趣旨にする。

- worker mode では loaded template class が PHP process 内に残るため、compiled view cache は基本不要である。
- cache directory を渡して有効化するのは、classic / non-worker runtime を使う場合に限定する。
- false で削れるのは template source 変更検知のコストだけである。
- false は古い template が残るリスクを持つため、Framework 標準では推奨しない。

つまり、Framework 標準は「env で速さと安全性を選ばせる」のではなく、runtime に合った明示的な構成を app container に書く方針で固定する。

## 用途別評価

### 個人ブログ

worker mode で運用するなら標準無効でよい。初回 render 後は template class が worker process 内に残る。

classic / non-worker runtime で運用する場合だけ、`cacheDirectory` を明示して有効化する。

### 超大量アクセスサイト

worker mode では View コンパイルキャッシュより、worker process 内の loaded class 再利用と OPcache の方が本筋である。

本当に効くのは CDN、reverse proxy、response cache、DB query cache、read replica、静的 asset 配信、worker mode、OPcache などとの組み合わせである。classic / non-worker runtime を選ぶ場合は、View コンパイルキャッシュを明示的に有効化する価値がある。

### 基幹システム

worker mode で運用するなら標準無効でよい。ただし、基幹システムでは「速いが古い画面が出る可能性がある」設定は許容しない。

classic / non-worker runtime で有効化する場合は、変更検知あり、app 別 cache directory、HTML response cache との分離で固定する。権限、CSRF、監査、承認状態など request / user / workflow に紐づく値を View cache に入れない限り、安全性は維持できる。

## 最終方針

このシステムでは worker mode を標準にするため、View コンパイルキャッシュは標準無効にする。classic / non-worker runtime を選ぶ場合だけ、app container で `cacheDirectory` を明示して有効化する。

したがって、採用方針は次の通り。

- View コンパイルキャッシュは標準では無効にする。
- data cache / response cache とは明確に分ける。
- `cacheDirectory` 明示時だけ `autoRefresh=true` 相当で固定する。
- `autoRefresh=false` の設定スイッチは作らない。
- View コンパイルキャッシュ自体を env / config switch で切り替えない。
- 有効化する場合、cache directory は app 別に分離する。
- container / config コンパイルキャッシュは現時点では採用しない。
