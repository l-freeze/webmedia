# リファクタリング作業チェックリスト

このファイルは、PHPStan 対応と設計整理を途中から再開できるようにするための作業メモである。

## PHPStan 対応

- [ ] PHPStan の現状エラーをカテゴリ別に整理する
- [x] PDO 周りの型安全化を行う
  - [x] `PDO::prepare()` / `PDO::query()` の `false` を Framework 側で閉じ込める
  - [x] `fetch()` の戻り値を `array<string, mixed>|null` に正規化する
  - [x] `fetchAll()` の戻り値を `list<array<string, mixed>>` に正規化する
  - [x] `fetchColumn()` の戻り値を用途別に正規化する
  - [x] Infrastructure/Persistence から直接 `PDOStatement|false` を扱う箇所を減らす
- [ ] callback 系配列関数を `foreach` へ寄せて解消する
  - [ ] `array_map()` を `foreach` へ置き換える
  - [ ] `array_filter()` を `foreach` へ置き換える
  - [ ] `array_reduce()` を `foreach` へ置き換える
  - [ ] `array_walk()` を使わないことを確認する
- [ ] `date()` / `strtotime()` の `false` 問題を日付 helper へ閉じ込める
- [ ] `Article::fromInput()` / `Page::fromInput()` の `mixed` cast を入力正規化 helper へ寄せる
- [ ] Controller / Application / Domain / Infrastructure の境界違反を PHPStan で検出できるようにする
- [ ] `composer analyse` を成功させる

## Worker Mode 対応

- [ ] request scope を `Container` に追加する
- [ ] `Request` / `Session` / `Csrf` を request scope に移す
- [ ] `Renderer` の global provider が request 固有 instance を固定保持しないようにする
- [ ] singleton にしてよい service と request scoped service をコード上で分離する
- [ ] FrankenPHP Worker mode の起動エントリを追加する
- [ ] Worker mode で連続リクエストして session / csrf / flash が混線しないことを確認する

## Middleware / Guard

- [ ] `cms.auth` middleware を実装する
- [ ] Controller 内の `Session` によるログイン判定を middleware へ移す
- [ ] `CurrentUser` DTO を導入する
- [ ] 認可が必要な UseCase に `CurrentUser` を渡す
- [ ] 重要操作には UseCase-level authorization を追加する

## Logging / Error Handling

- [ ] `LoggerInterface` の実装を追加する
- [ ] `AuditLoggerInterface` の実装を追加する
- [ ] 例外を HTTP response に変換する error handler を追加する
- [ ] request id を発行し、log / audit log に載せる
- [ ] 500 系のユーザー表示と内部ログを分離する

## Architecture Test

- [ ] `apps/front` から `App\Cms` へ依存していないことを検査する
- [ ] `apps/cms` から `App\Front` へ依存していないことを検査する
- [ ] `Application` が `Infrastructure` に依存していないことを検査する
- [ ] `Domain` が `Application` / `Infrastructure` に依存していないことを検査する
- [ ] Controller の不要な `as` alias を検査する
- [ ] UseCase `Input` の named arguments 利用を検査する
