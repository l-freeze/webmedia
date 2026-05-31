# Node.js / TypeScript でリプレイスする場合の方針

このドキュメントは、将来このシステムを Node.js / TypeScript で再実装する場合の検討メモである。現時点でリプレイスを実施する予定はない。

前提として、現在の PHP 実装は `Controller` / `Application Interactor` / `Input` / `Output` / `Repository` を分けている。この責務分離は Node.js へ移しても維持する。リプレイス時に重要なのは、単に言語を置き換えることではなく、現在の境界を崩さずに、Node.js / TypeScript の書き味に合う形へ移すことである。

## 1. 結論

このシステムを Node.js で軽く再実装するなら、第一候補は `Fastify + TypeScript` とする。

理由:

- Node.js らしい軽さを保ちやすい。
- `Controller` 相当の route handler を薄く保ち、UseCase を普通の関数として分離できる。
- class / decorator / DI container を全面採用しなくてよい。
- TypeScript の union type によって `Result` / `Option` 風の戻り値を表現しやすい。
- 既存の Interactor / Output に近い考え方を、関数と plain object で自然に表現できる。
- CMS、認証、CSRF、multipart upload、動画ファイル、HTML SSR を扱うには、Hono より Fastify の方が Node サーバーアプリとして堅い。

推奨しないわけではないが、`NestJS` はこの方針では第一候補にしない。NestJS は規約が強く、大規模チーム開発や Java / Spring / Angular 的な構成には向くが、F# / OCaml 的な明示的で関数中心の読み味からは遠い。

## 2. NestJS と Fastify の関係

NestJS と Fastify は同じ種類のものではない。

```text
Fastify
  HTTPサーバーフレームワーク
  route / hook / plugin / schema validation を提供する

NestJS
  アプリケーションフレームワーク
  Controller / Provider / Module / Guard / Pipe / Interceptor / DI を提供する
  HTTPエンジンとして Express または Fastify を選べる
```

NestJS は内部 HTTP adapter として Fastify を使える。しかし、`NestJS + Fastify adapter` は Fastify 単体とは別物である。

```text
Fastify単体:
  request -> route handler -> UseCase関数

NestJS + Fastify adapter:
  request -> Nest Controller -> Guard/Pipe/Interceptor -> Provider/UseCase
          -> HTTP層の実体はFastify
```

NestJS で Fastify adapter を使っても、NestJS の decorator、module、DI、lifecycle は残る。したがって、Node.js らしく軽くしたい場合は Fastify 単体を優先する。

## 3. Hono の扱い

Hono は完全に除外しない。軽量 API / BFF / Edge runtime を視野に入れるなら良い選択肢である。

ただし、このシステムのリプレイス候補としては Fastify を優先する。

理由:

- CMS 管理画面がある。
- セッション認証と CSRF がある。
- multipart upload を扱う。
- 画像と動画ファイルを扱う。
- HTML SSR が必要になる。
- 将来的に ffmpeg による動画変換ジョブを持つ可能性がある。
- Node サーバーアプリとしての運用情報は Fastify の方が取りやすい。

Hono が合うケース:

- 公開 API 中心。
- 小さい BFF。
- Edge runtime も対象にする。
- JSX で薄い HTML を返す。
- セッションや大容量アップロードが主役ではない。

このプロジェクトでは「軽さ」だけでなく「CMS とメディア管理を安全に運用すること」も必要になるため、Hono は第二候補とする。

## 4. 推奨スタック

### 4.1 Web framework

第一候補:

```text
Fastify + TypeScript
```

候補ライブラリ:

```text
fastify
@fastify/multipart
@fastify/cookie
@fastify/session
@fastify/static
@fastify/view
```

CSRF は `@fastify/csrf-protection` を検討する。ただし、既存仕様に合わせたい場合は独自 middleware として実装してもよい。

### 4.2 DB

第一候補:

```text
Kysely + pg
```

理由:

- SQL に近い読み味を保てる。
- Repository 内に閉じ込めやすい。
- Prisma のように ORM 中心の設計へ引っ張られにくい。
- 既存DBスキーマを維持した移植に向く。

代替:

```text
Drizzle ORM
```

Prisma は便利だが、リプレイス時に Prisma のモデル中心へ設計が寄りやすい。現在の Repository 境界を維持したい場合は Kysely の方が扱いやすい。

### 4.3 validation

Fastify 標準の JSON Schema / Ajv を基本にする。

TypeScript 側の型と schema を近づけたい場合:

```text
@sinclair/typebox
```

Zod は開発体験が良いが、Fastify の schema validation と二重管理になりやすい。API schema を Fastify に寄せるなら TypeBox を優先する。

### 4.4 view

既存の Latte に近いサーバーサイドHTMLを維持するなら:

```text
Eta
Nunjucks
```

CMS 管理画面は React 化せず、まずは SSR template でよい。公開フロントを React / Next.js にしたい場合だけ別途検討する。

### 4.5 markdown

候補:

```text
markdown-it
unified / remark / rehype
```

単純な Markdown 表示なら `markdown-it`。将来的に AST 変換、目次生成、HTML sanitizer を強く管理したいなら `unified` 系を検討する。

### 4.6 media / video

アップロード管理:

```text
@fastify/multipart
node:fs/promises
```

動画変換:

```text
ffmpeg
execa
```

動画変換本体は Node.js で自作しない。Node.js は ffmpeg を起動し、終了コード、stderr、生成ファイル、DB状態を管理する制御役にする。

## 5. ディレクトリ構成案

最初から monorepo を重くしすぎない。まずは単一アプリ内で責務を分ける。

```text
src/
  main.ts
  app.ts

  features/
    article/
      article.routes.ts
      articleTypes.ts
      listArticles.ts
      showArticle.ts
      articleRepository.ts

    media/
      media.routes.ts
      mediaTypes.ts
      listMedia.ts
      uploadMedia.ts
      deleteMedia.ts
      convertMedia.ts
      mediaRepository.ts
      mediaStorage.ts

    auth/
      auth.routes.ts
      login.ts
      logout.ts
      requireAdmin.ts

    page/
      page.routes.ts
      pageTypes.ts
      savePage.ts
      pageRepository.ts

  infrastructure/
    db/
      db.ts
      transaction.ts
    storage/
      publicMediaStorage.ts
    ffmpeg/
      runFfmpeg.ts

  shared/
    result.ts
    option.ts
    csrf.ts
    session.ts
    validation.ts
    date.ts

  views/
    layout.eta
    media/
      index.eta
    article/
      edit.eta
```

機能単位で `features/` に寄せる。`application/` / `domain/` / `infrastructure/` を厳密にトップレベル分離してもよいが、Node.js ではファイル移動が増えすぎると読みづらくなる場合がある。

現在のPHP構造をより強く維持したい場合は次の構成でもよい。

```text
src/
  application/
  domain/
  infrastructure/
  presentation/
```

ただし Node.js / TypeScript では、機能単位の近接配置の方が読みやすいことが多い。

## 6. コードの書き方

### 6.1 基本方針

推奨:

- class より関数を優先する。
- DI container より明示的な `deps` object を使う。
- 例外より `Result` を使う。
- nullable より union type で状態を表す。
- 継承より合成を使う。
- decorator に処理を隠さない。
- framework 依存は route handler 付近に閉じ込める。
- UseCase は Fastify の request / reply を知らないようにする。

避ける:

- NestJS 的な decorator / DI container 前提の構成。
- `fp-ts` を濃く使った疑似Haskell/F#化。
- 何でも class 化する設計。
- Repository 内から HTTP response を返す設計。
- UseCase から template を直接 render する設計。

TypeScript は F# ではないため、F# を完全再現しようとしない。`pipe` や `Either` を過剰に使うより、TypeScript の discriminated union と普通の `if` を使う方が読みやすい。

### 6.2 Result 型

```ts
export type Result<T, E> =
  | { type: 'ok'; value: T }
  | { type: 'error'; error: E }

export const ok = <T>(value: T): Result<T, never> => ({
  type: 'ok',
  value,
})

export const error = <E>(error: E): Result<never, E> => ({
  type: 'error',
  error,
})
```

### 6.3 UseCase

```ts
type UploadMediaDeps = {
  mediaRepository: MediaRepository
  mediaStorage: MediaStorage
}

export type UploadMediaInput = {
  file: UploadedFile
  altText: string
  caption: string
}

export type UploadMediaOutput =
  | { type: 'uploaded'; path: string }
  | { type: 'failed'; message: string }

export const createUploadMedia =
  (deps: UploadMediaDeps) =>
  async (input: UploadMediaInput): Promise<UploadMediaOutput> => {
    const validation = validateMediaFile(input.file)

    if (validation.type === 'error') {
      return { type: 'failed', message: validation.error }
    }

    const stored = await deps.mediaStorage.store(input.file)

    if (stored.type === 'error') {
      return { type: 'failed', message: stored.error }
    }

    await deps.mediaRepository.insert({
      fileName: input.file.originalName,
      path: stored.value.path,
      mimeType: input.file.mimeType,
      fileSize: input.file.size,
      altText: input.altText,
      caption: input.caption,
    })

    return { type: 'uploaded', path: stored.value.path }
  }
```

UseCase は HTTP を知らない。現在の PHP Interactor と同じく、入力を受けて出力を返すだけにする。

### 6.4 Route handler

```ts
export const registerMediaRoutes = (
  app: FastifyInstance,
  deps: MediaRouteDeps,
) => {
  const uploadMedia = createUploadMedia({
    mediaRepository: deps.mediaRepository,
    mediaStorage: deps.mediaStorage,
  })

  app.post('/admin/media', {
    preHandler: [deps.requireAdmin, deps.validateCsrf],
  }, async (request, reply) => {
    const input = await toUploadMediaInput(request)
    const output = await uploadMedia(input)

    if (output.type === 'failed') {
      request.session.flash('error', output.message)
      return reply.redirect('/admin/media')
    }

    request.session.flash('success', 'メディアをアップロードしました')
    return reply.redirect('/admin/media')
  })
}
```

route handler は HTTP、session、redirect、template だけを扱う。業務処理は UseCase へ委譲する。

### 6.5 Repository

```ts
export type MediaRepository = {
  list: () => Promise<Media[]>
  insert: (media: NewMedia) => Promise<void>
  deleteById: (id: number) => Promise<DeleteMediaResult>
}

export const createMediaRepository = (db: Kysely<Database>): MediaRepository => ({
  list: async () =>
    db
      .selectFrom('media_files')
      .selectAll()
      .orderBy('created_at', 'desc')
      .execute(),

  insert: async media => {
    await db
      .insertInto('media_files')
      .values({
        file_name: media.fileName,
        path: media.path,
        mime_type: media.mimeType,
        file_size: media.fileSize,
        alt_text: media.altText || null,
        caption: media.caption || null,
        created_at: new Date(),
      })
      .execute()
  },

  deleteById: async id => {
    // 実装時は transaction 内で article eyecatch 参照解除も行う。
    return { type: 'deleted' }
  },
})
```

Repository も class である必要はない。DB接続を受け取り、関数を持つ object を返せばよい。

## 7. 現在の PHP 構造との対応

```text
apps/cms/config/routes.php
  -> features/*/*.routes.ts

Controller
  -> Fastify route handler

Application/*/Interactor
  -> createXxxUseCase(deps)

Input
  -> Input type

Output
  -> Output discriminated union

Domain Entity / Value Object
  -> domain type / constructor function / validation function

Repository interface
  -> TypeScript type

PdoRepository
  -> Kysely repository implementation

Latte template
  -> Eta / Nunjucks template

Session / Csrf / AuthGuard
  -> Fastify plugin / preHandler
```

## 8. 動画変換ジョブ

動画変換はリアルタイムに行わない。アップロード時は元ファイルを保存し、DBに変換状態を保存する。

追加カラム例:

```text
media_files
  conversion_status
  converted_path
  poster_path
  conversion_error
  conversion_started_at
  conversion_finished_at
```

状態:

```text
uploaded
processing
ready
failed
```

ワーカー構成:

```text
node dist/workers/mediaConvertWorker.js
```

最初は DB polling でよい。

```ts
const job = await mediaRepository.findNextUploadedVideo()

if (!job) {
  await sleep(3000)
  return
}

await mediaRepository.markProcessing(job.id)
const result = await ffmpeg.convertToMp4(job.path)

if (result.type === 'ok') {
  await mediaRepository.markReady(job.id, result.value)
} else {
  await mediaRepository.markFailed(job.id, result.error)
}
```

件数が増えたら Redis queue を検討する。最初から BullMQ を入れる必要はない。

## 9. 移行手順案

一括リプレイスは避ける。

1. DBスキーマ互換のまま Node.js アプリを作る。
2. 公開フロントの read-only 画面から移植する。
3. CMSログインとセッションを移植する。
4. CMS記事一覧、記事詳細、プレビューを移植する。
5. 記事保存、履歴、復元を移植する。
6. メディアアップロード、削除、動画対応を移植する。
7. 問い合わせ、設定、固定ページを移植する。
8. PHP版とNode版を同じDBに向けて比較する。
9. URL互換とHTML出力差分を確認する。
10. 切り替える。

## 10. 判断まとめ

このプロジェクトで Node.js / TypeScript に移すなら、推奨は次の通り。

```text
Framework:
  Fastify

Language:
  TypeScript

DB:
  Kysely + pg

Template:
  Eta または Nunjucks

Validation:
  Fastify JSON Schema + TypeBox

Upload:
  @fastify/multipart

Video:
  ffmpeg + execa

Architecture:
  関数中心
  plain object中心
  deps objectによる明示的依存注入
  Result union
  route handlerは薄くする
```

NestJS は、規約強めの業務アプリを作る場合には有力である。しかし、このプロジェクトで重視する「Node.js らしい軽さ」「F# / OCaml に近い明示性」「迷いなく読める一貫性」を優先するなら、Fastify 単体で構成する方が合っている。
