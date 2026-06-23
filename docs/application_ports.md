# Application Port 判断基準

## 目的

このドキュメントは、Application から外側の機能を使うときに、Port を切るべきか、具象実装をそのまま外側で使えばよいかを判断するための基準である。

本プロジェクトでは、Port を「Application が外側に要求する能力を表す Interface」として扱う。Port は独立したレイヤー名ではなく、依存方向を保つために Application 側へ置く境界である。

## 基本方針

Application はユースケースの手順と業務判断を表す。Application の中に、DB、ファイルシステム、HTTP API、外部ライブラリ、PHP 組み込みの技術詳細、現在時刻、メール送信、セッションの読み書きなどの外側の都合を直接入れない。

ただし、Application がそれらの機能を必要とすることはある。その場合、Application 側には「何が必要か」だけを Interface として置き、具体的な実装は Infrastructure、Presentation、Framework、Shared など外側の層に置く。

```text
Application Interactor
  -> Application Port interface

Infrastructure / Presentation / Framework / Shared
  -> implements Application Port interface
  -> DB / file / HTTP API / library / PHP built-in
```

Port は、Application に内包してはいけないものを安全に使うための入口である。

## Port を切ってよい条件

Port を切ってよいのは、次の条件を満たす場合に限る。

- Application のユースケースを実現するために、その能力が必要である。
- その能力の実体が Application の内側に置けない技術詳細、外部I/O、外部サービス、実行環境依存である。
- Application が具象実装を直接知ると、依存方向が外側へ向いてしまう。
- Interface 名が、外側の実装名ではなく Application 側の要求を表している。

たとえば CMS ログインでは、Application が必要としているのは `password_verify()` そのものではなく「入力パスワードが保存済みハッシュと一致するかを検証する能力」である。そのため、Application 側に `PasswordVerifier` を置き、PHP の `password_verify()` 呼び出しは Infrastructure 側の実装に閉じ込めるのが自然である。

## Port を切るべきではない条件

次の理由だけで Interface を作ってはならない。

- 将来差し替えるかもしれない。
- テストで mock したい。
- 複数実装がありそう。
- DI コンテナに登録しやすい。
- class 名に対して機械的に interface 名を作れる。
- 技術名を抽象化すれば設計が良く見える。

Port が必要に見える場合でも、先に責務分割、UseCase 分割、配置、データ構造を疑う。

## Port と Infrastructure の関係

メール送信、現在時刻、認証中ユーザー、パスワード検証、Markdown 変換、ファイル保存、外部 API 呼び出し、トランザクションは、実装としては Infrastructure、Presentation、Framework、Shared の責任範囲に置かれることが多い。

重要なのは、実装の置き場所ではなく、Application が何へ依存するかである。

```text
悪い例:
Application -> Infrastructure Mailer
Application -> PDO
Application -> CommonMark converter
Application -> password_verify()

良い例:
Application -> MailSender port
Application -> TransactionRunner port
Application -> MarkdownRenderer port
Application -> PasswordVerifier port
Infrastructure -> implements those ports
```

つまり、Port は Infrastructure の代替レイヤーではない。Infrastructure に置くべき実装を Application から直接見ないための、Application 側の契約である。

## 例

### パスワード検証

Application が必要とする言葉:

```text
PasswordVerifier::verify(plainPassword, passwordHash): bool
```

外側の実装:

```text
NativePasswordVerifier -> password_verify()
```

Application は PHP の関数名、アルゴリズム、再ハッシュ判断、監査ログなどの技術詳細を知らない。

### 現在時刻

Application が公開期間、予約公開、期限切れ判定を行うなら、現在時刻はユースケース判断の材料になる。ただし `new DateTimeImmutable()` を Application 内で直接呼ぶと、実行時刻に依存してテストしづらく、batch や再実行時の基準時刻も差し替えにくい。

この場合は `Clock` のような Port を置く価値がある。

### Markdown 変換

Presentation や Infrastructure が表示用に Markdown を HTML へ変換するだけなら、具象 `Markdown` を外側で直接使ってよい。

一方、UseCase が「保存前に本文を安全な HTML に変換する」「プレビュー HTML を Application Output として返す」などを始めるなら、Application が Markdown ライブラリを直接知ることになる。この場合は次のどちらかを検討する。

- そもそもその変換は Application の責務か見直す。
- Application の責務であるなら `MarkdownRenderer` などの Port を切る。

## Laravel の Contract との違い

Laravel の Contract の多くは、業務 Application の Port ではなく、フレームワーク機能の差し替え点である。Cache、Queue、Mail、Filesystem、Auth、Events などは、Laravel という実行基盤の拡張境界であり、Application のユースケースが外側へ要求する能力とは粒度が違う。

本プロジェクトでは Laravel 的に何でも Contract 化しない。Application が本当に外側へ要求している能力だけを Port にする。

## 判断手順

Port が必要か迷ったら、次の順で判断する。

1. その処理は Application のユースケース判断に必要か。
2. その処理の実体は Application に置いてよい純粋な計算か。
3. DB、ファイル、HTTP、外部サービス、ライブラリ、時刻、セッション、暗号など外側の都合を含むか。
4. Application が具象へ依存すると、依存方向が外側へ向かないか。
5. Interface 名は Application 側の要求を表しているか。

1 が false なら Application へ入れない。Presentation / Infrastructure / Framework 側で扱う。

2 が true なら Port は不要で、Application や Domain の value / service として実装できる。

3 と 4 が true なら Port を切る候補である。

5 が false なら、Port 名ではなく責務理解がまだ曖昧である。先に名前と責務を詰める。

## このプロジェクトでの配置

Query interface は既存方針どおり、各 UseCase 配下の `Query` に置く。

Repository interface は Domain 配下の `Repository` に置く。

Query / Repository 以外の Port は、まず必要としている UseCase または機能コンテキストの Application 配下に小さく置く。最初から汎用 `Port` ディレクトリを作らない。

例:

```text
apps/cms/src/Application/Auth/Login/PasswordVerifier.php
apps/cms/src/Infrastructure/Auth/Password/NativePasswordVerifier.php
```

複数 UseCase から共有され、同じ Application コンテキストの能力として安定した場合だけ、より上位の Application 配下へ移動する。
