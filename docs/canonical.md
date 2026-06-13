# Canonical URL 実装プラン

## 目的

フロント公開ページのうち、検索エンジンに正規URLを明示すべきページへ `<link rel="canonical">` を出力する。

canonical URL は、同じ内容に複数URLで到達できる場合に「このページの正式なURLはこれ」と検索エンジンへ伝えるためのメタ情報である。クエリ文字列、HTTP/HTTPS、www有無、外部流入用パラメータなどによる評価分散や重複扱いを避ける目的で使う。

## 方針

canonical は認証やCORSのような横断的なHTTP制御ではなく、ページごとの意味を持つSEOメタ情報として扱う。

そのため、Front専用middlewareでHTMLへ自動挿入するのではなく、canonicalを出すページのControllerから明示的にテンプレートへ渡す。URL生成の重複は `Presentation/Seo` の共通クラスへ集約し、`Presentation/Http/Controller` には「このページではcanonicalを出す」という判断だけを残す。

## 対象ページ

初期対応では、公開ページとして正規URLを明示したい以下のみを対象にする。

- 記事詳細: `/articles/{slug}`
- カテゴリ詳細: `/categories/{slug}`
- タグ詳細: `/tags/{slug}`
- 固定ページ: `/pages/{slug}`

以下は初期対応の対象外にする。

- トップページ
- カテゴリ一覧: `/categories`
- タグ一覧: `/tags`
- 検索結果: `/search`
- 問い合わせページ
- 状態表示ページ
- 404などのエラーページ

対象外ページは、ページング、検索条件、運用方針によって正規URLの意味が変わりやすい。必要になった時点で個別に判断して追加する。

## URL生成ルール

- canonical は絶対URLで出力する。
- ベースURLは環境変数 `FRONT_SITE_URL` から取得する。
- `FRONT_SITE_URL` が未設定の場合は空文字列とし、canonicalは出力しない。
- パスはControllerで扱う公開URLを使う。
- クエリ文字列は含めない。
- ベースURL末尾の `/` とパス先頭の `/` は正規化し、二重スラッシュを避ける。

例:

```text
FRONT_SITE_URL=https://example.com
/articles/php-architecture
=> https://example.com/articles/php-architecture
```

## 実装内容

1. Front側にcanonical URL生成クラスを追加する。
   - 例: `App\Front\Presentation\Seo\CanonicalUrl`
   - `fromPath(string $path): ?string`
   - `article(string $slug): ?string`
   - `category(string $slug): ?string`
   - `tag(string $slug): ?string`
   - `page(string $slug): ?string`

2. `apps/front/config/container.php` で `CanonicalUrl` を登録する。
   - `FRONT_SITE_URL` を読み取って注入する。
   - 未設定時は空文字列を渡す。

3. フロント共通レイアウトにcanonical出力を追加する。
   - `canonicalUrl` が空でない場合だけ `<link rel="canonical" href="...">` を出す。

4. 対象Controllerへ `CanonicalUrl` を注入する。
   - 記事詳細Controller
   - カテゴリ詳細Controller
   - タグ詳細Controller
   - 固定ページController

5. 対象Controllerでテンプレート引数へ `canonicalUrl` を渡す。
   - 404レスポンスでは渡さない。
   - カテゴリ/タグが見つからない場合に200で空一覧を返す既存挙動があるため、canonicalを出すのは詳細データがある場合に限定する。

## テスト方針

- `CanonicalUrl` の単体テストを追加する。
  - ベースURLとパスを結合できる。
  - ベースURL未設定では `null` になる。
  - 末尾/先頭スラッシュを正規化する。
  - クエリ文字列を含むパスを渡してもクエリを落とす。

- レイアウト出力テストを追加する。
  - `canonicalUrl` がある場合は `<link rel="canonical">` が出る。
  - `canonicalUrl` がない場合は出ない。

- 既存の全体テストと静的解析を実行する。
  - `docker compose run --rm app composer test`
  - `docker compose run --rm app composer analyse`

## ブラウザ確認

ローカルでアプリを起動し、Playwrightで対象ページを開いて以下を確認する。

```js
document.querySelector('link[rel="canonical"]')?.href
```

確認対象:

- `/articles/{公開済みslug}`
- `/categories/{公開済みカテゴリslug}`
- `/tags/{公開済みタグslug}`
- `/pages/{公開済み固定ページslug}`

対象外ページではcanonicalが出ないことも確認する。
