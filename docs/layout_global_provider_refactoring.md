# Layout Global Provider Refactoring

## 背景

Front の layout global data は、`App\Front\Presentation\View\LayoutGlobalProvider` が組み立てている。

現在の cache は短 TTL の性能改善であり、Shared には cache 機構だけを置き、Front の Presentation 層が layout 表示用 data を cache する形にしている。

この判断は、layout global data が以下のような表示都合の合成 data であるためである。

- published page links
- site settings
- categories

ただし、cache 制御が複雑化すると、layout data を組み立てる責務と cache の責務が同じ class に混ざりすぎる。

## 現時点の方針

現時点では過剰な抽象化を避ける。

`LayoutGlobalProvider` は以下の程度に留める。

- `globals()` は cache hit / miss と TTL 無効化を扱う。
- `loadGlobals()` は query から layout data を組み立てるだけにする。
- provider interface や decorator class はまだ増やさない。

この形なら、cache 制御と layout data 組み立てを読み分けられる一方で、file 数と wiring は増えない。

## 複雑化の兆候

以下のいずれかが必要になったら、decorator 分離を検討する。

- CMS 更新時の明示 invalidation を入れる。
- cache key が複数になる。
- locale、theme、preview、device、user state で layout data が変わる。
- site settings、page links、categories で TTL を分けたくなる。
- cache stampede 対策が必要になる。
- cache 書き込み失敗や破損 cache への fallback policy を明示したくなる。
- 他の Front provider や Query でも同じ TTL cache pattern が増える。
- `LayoutGlobalProvider` の test が cache 制御と data 組み立てで読みにくくなる。

## 改善案

複雑化した場合は、Presentation 層内で provider と cache decorator を分ける。

```text
apps/front/src/Presentation/View/
  LayoutGlobalProviderInterface.php
  QueryingLayoutGlobalProvider.php
  CachedLayoutGlobalProvider.php
```

`LayoutGlobalProviderInterface` は layout globals を返す contract だけを持つ。

```php
interface LayoutGlobalProviderInterface
{
    /** @return array<string, mixed> */
    public function globals(): array;
}
```

`QueryingLayoutGlobalProvider` は layout data の組み立てだけを担当する。

- `IPageLinkQuery`
- `ISiteSettingQuery`
- `ICategoryQuery`

を使い、cache key、TTL、cache hit / miss は知らない。

`CachedLayoutGlobalProvider` は cache 制御だけを担当する。

- `LayoutGlobalProviderInterface $inner`
- `CacheInterface $cache`
- `int $ttlSeconds`
- cache key

を持ち、cache miss 時だけ `$inner->globals()` を呼ぶ。

container では以下のように decorator として組み立てる。

```php
$rawProvider = new QueryingLayoutGlobalProvider(
    pageLinks: $pageLinkQuery,
    settings: $siteSettingQuery,
    categories: $categoryQuery,
);

return new CachedLayoutGlobalProvider(
    inner: $rawProvider,
    cache: $cache,
    ttlSeconds: $ttlSeconds,
);
```

## 採用しない案

Shared に layout cache service を置く案は採用しない。layout globals は Front 固有の表示都合であり、Shared に置くと app 固有責務が横断層に漏れる。

UseCase に layout global data を寄せる案も慎重に扱う。カテゴリ一覧やタグ一覧そのものを cache するなら UseCase / Query decorator は妥当だが、site settings、page links、categories を layout template 用に束ねる処理は Presentation の責務である。

container closure への cache 直書きも採用しない。container は wiring に留め、layout data の組み立てや cache policy は named class に置く。
