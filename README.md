# WEB Media

Laravel / WordPress を使わない、小規模WEBメディア + CMS の軽量 PHP アプリケーションです。

## 開発環境

```bash
cp .env.example .env
make up
make install
make migrate
make seed
```

フロント:

- `http://localhost:8080/`

CMS:

- `http://localhost:8080/admin/login`
- 初期メールアドレス: `admin@example.test`
- 初期パスワード: `password`

## テスト

```bash
make test
```

## 構成

- `src/Framework`: DI、HTTP、Routing、View、Security、DB 接続などのアプリ非依存な軽量基盤
- `apps/front`: 読者向けフロント。公開済み記事の Query 中心
- `apps/cms`: 管理画面。認証、記事保存、履歴、管理機能
- `database/migrations`: Phinx マイグレーション
- `database/seeds`: ローカル確認用シード
- `public`: Web 公開ディレクトリ

## 設計思想

Framework は意図的に小さく保ちます。

Framework は最小の実行基盤だけを提供します。アプリケーション要件は Kernel、Shared、各 App に置きます。機能は、理解しやすく、差し替えやすく、削除しやすい場合にだけ追加します。

賢い抽象より、退屈で明示的で読みやすいコードを優先します。

## Design Philosophy

The Framework is intentionally kept small.

The Framework provides only the minimum execution foundation. Application concerns belong to Kernel, Shared, or each App. A feature is added only when it keeps the system easier to understand, replace, or delete.

We prefer boring, explicit, readable code over clever abstractions.

## PSR

このプロジェクトは Composer autoload と相性のよい範囲で PSR を採用します。

- PSR-3: `Psr\Log\LoggerInterface` をログの標準 contract として使います。
- PSR-4: `App\Framework`、`App\Kernel`、`App\Shared`、`App\Front`、`App\Cms` を Composer autoload で解決します。
- PSR-11: `App\Framework\Container\Container` は `Psr\Container\ContainerInterface` に準拠しています。
- PSR-16: `apps/shared` は `Psr\SimpleCache\CacheInterface` を共通キャッシュ contract として使います。

HTTP の内部モデルは軽量性と可読性を優先し、独自の `Request` / `Response` を使います。RoadRunner など外部 runtime との境界では必要に応じて PSR-7 と bridge します。
