# WEBメディア アーキテクチャ規約

## 1. 目的

このドキュメントは、本プロジェクトを実装する際に、アーキテクチャ境界、ディレクトリ構造、責務、依存関係を誤らないための規約である。

本プロジェクトは PHP で実装する WEB メディアであり、以下の2つのアプリケーションコンテキストを持つ。

- `apps/front`
  - 読者向けの公開フロント
  - 公開済み記事、固定ページ、カテゴリ、タグ、検索、問い合わせを扱う
- `apps/cms`
  - 管理者向け CMS
  - 記事編集、分類管理、メディア、設定、問い合わせ、ゴミ箱を扱う

`front` と `cms` は別アプリケーションとして扱う。一方がもう一方へ依存してはならない。

```text
apps/
  front/
  cms/
```

禁止:

```php
// cms 側で禁止
use App\Front\...;

// front 側で禁止
use App\Cms\...;
```

## 2. 基本方針

本プロジェクトでは以下を採用する。

- クリーンアーキテクチャー
- ポート & アダプター
- CQRS
- UseCase 単位の Application
- Constructor Injection
- Composer PSR-4 Autoload

このプロジェクト自体が WEB メディアである。そのため、`WebMedia`、`Common`、`Core`、`Utils`、`Helpers` のような曖昧なアプリケーションディレクトリを作ってはならない。

共通基盤は責務ごとに明確に分ける。

- `src/Framework`: DI、HTTP、Routing など、アプリケーション都合を持たない軽量 Framework core
- `apps/kernel`: 起動、runtime、DB factory など、実行基盤を組み立てる Kernel
- `apps/shared`: `front` / `cms` の両方から使うアプリケーション側の共有基盤

`apps/shared` は許可する。ただし、雑多な helper や業務ロジックの避難場所にしてはならない。`front` / `cms` のどちらか一方に閉じる機能は、それぞれのアプリケーション配下に置く。

```text
src/
  Framework/
    Container/
    Http/
    Routing/
    View/
    Database/
    Security/
    Content/
```

`apps/common`、`apps/modules`、`apps/framework` は禁止する。`apps` 直下に追加する共有ディレクトリは、責務が明確な `kernel` / `shared` に限定する。

## 3. 依存ルール

依存は内側へ向ける。

許可:

```text
Presentation Controller -> Application Interactor
Application Interactor -> Application Query interface
Application Interactor -> Domain Repository interface
Application Interactor -> Domain Entity / ValueObject
Infrastructure implementation -> Application Query interface
Infrastructure implementation -> Domain Repository interface
Infrastructure implementation -> Domain Entity / ValueObject
```

禁止:

```text
Application -> Infrastructure
Domain -> Infrastructure
Domain -> Application
front -> cms
cms -> front
```

### 3.1 Interface 導入ルール

アプリケーションコードでは、Interface は依存制御のためにだけ導入する。

Interface を作ってよい主な用途:

- Application から Infrastructure の具象実装へ依存しないための Query port
- Domain から Infrastructure の具象実装へ依存しないための Repository port
- 外部サービス、外部ライブラリ、永続化、HTTP、ファイルシステムなどの技術詳細を内側から隔離する境界
- Framework / Kernel / Shared の基盤機能が、実装差し込み点を明示的に提供する場合

禁止:

```text
複数実装がありそうだから Interface を作る
将来差し替えるかもしれないから Interface を作る
テストで mock したいだけで Interface を作る
同じメソッド形状の class が2つあるから Interface を作る
型の共通化や分類名として Interface を作る
```

Interface が必要に見える場合は、先に責務分割、UseCase 分割、配置、データ構造を見直す。

たとえば CSV / JSON / HTML などの出力形式が複数ある場合でも、呼び出し側が形式を選び、それぞれが `Response` を返すだけなら `ExporterInterface` は不要である。`CsvResponse`、`JsonResponse`、`HtmlResponse` のような具象の出力変換で表現する。

`ExporterInterface` のような Interface を作ってよいのは、呼び出し側が具体形式を知らず、同じ契約で実装を差し替える依存制御が現在必要な場合に限る。

Shared に置く Interface も例外ではない。Shared の Interface は、複数アプリから使われる外部境界、または Framework / Kernel 的な基盤の差し込み点を表す場合に限る。

## 4. ディレクトリ構造

### 4.1 ルート

```text
webmedia/
  apps/
    kernel/
    shared/
    front/
    cms/
  src/
    Framework/
  public/
  database/
  storage/
  tests/
  composer.json
```

### 4.2 Front

```text
apps/front/
  config/
    container.php
    routes.php
  src/
    Application/
      Article/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
          Value/
            Article.php
        Show/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
          Value/
            Article.php
        Search/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
          Value/
            Article.php
      Category/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            ICategoryQuery.php
          Value/
            Category.php
        Show/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
            ICategoryQuery.php
          Value/
            Article.php
            CategoryDetail.php
      Tag/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            ITagQuery.php
          Value/
            Tag.php
        Show/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
            ITagQuery.php
          Value/
            Article.php
            TagDetail.php
      Page/
        Show/
          Input.php
          Interactor.php
          Output.php
          Query/
            IPageQuery.php
      Inquiry/
        Create/
          Input.php
          Interactor.php
          Output.php
    Domain/
      Repository/
        IInquiryRepository.php
    Presentation/
      Http/
        Controller/
      Seo/
        CanonicalUrl.php
    Infrastructure/
      Persistence/
        PdoArticleQuery.php
        PdoCategoryQuery.php
        PdoTagQuery.php
        PdoPageQuery.php
        PdoInquiryRepository.php
  templates/
```

### 4.3 CMS

```text
apps/cms/
  config/
    container.php
    routes.php
  src/
    Application/
      Dashboard/
        Show/
          Input.php
          Interactor.php
          Output.php
          Query/
            IDashboardQuery.php
      Article/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
        Edit/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
        Save/
          Input.php
          Interactor.php
          Output.php
        Preview/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleQuery.php
        History/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleHistoryQuery.php
        Restore/
          Input.php
          Interactor.php
          Output.php
      ArticleVersion/
        Preview/
          Input.php
          Interactor.php
          Output.php
          Query/
            IArticleVersionQuery.php
      Page/
        List/
        Edit/
          Input.php
          Interactor.php
          Output.php
          Query/
            IPageQuery.php
        Save/
          Input.php
          Interactor.php
          Output.php
        History/
          Input.php
          Interactor.php
          Output.php
          Query/
            IPageHistoryQuery.php
      Taxonomy/
        List/
        Save/
      Media/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            IMediaQuery.php
      Inquiry/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            IInquiryQuery.php
      Setting/
        Edit/
          Input.php
          Interactor.php
          Output.php
          Query/
            ISettingQuery.php
        Save/
          Input.php
          Interactor.php
          Output.php
      Trash/
        List/
          Input.php
          Interactor.php
          Output.php
          Query/
            ITrashQuery.php
    Domain/
      Entity/
      Value/
        ArticleId.php
      Repository/
        IArticleRepository.php
        IPageRepository.php
        ITaxonomyRepository.php
      Exception/
    Auth/
      Domain/
      Infrastructure/
    Presentation/
      Http/
        Controller/
        Middleware/
    Infrastructure/
      Persistence/
        PdoArticleRepository.php
        PdoArticleQuery.php
        PdoDashboardQuery.php
        PdoInquiryQuery.php
        PdoMediaQuery.php
        PdoPageRepository.php
        PdoSettingQuery.php
        PdoTaxonomyRepository.php
        PdoTrashQuery.php
  templates/
```

`Auth` は認証境界であり、WEB メディアのコンテンツユースケースではないため分離してよい。ただし、それを理由に `Article`、`Tag`、`Page`、`Inquiry` などをトップレベルの小アプリケーションとして分割してはならない。

## 5. Application ルール

Application は対象概念とユースケースアクションで分ける。

```text
Application/<Feature>/<Action>/
  Input.php
  Interactor.php
  Output.php
  Value/
  Query/
```

例:

```text
Application/Dashboard/Show/
Application/Article/List/
Application/Article/Save/
Application/Tag/List/
Application/Tag/Save/
Application/Inquiry/Create/
```

`Application/<Feature>/` の直下に置く通常ディレクトリは Action だけである。Action ディレクトリは原則として `Input.php`、`Interactor.php`、`Output.php` を持つ。

禁止:

```text
Application/Article/Policy/
Application/Media/ImagePicker/
Application/Sitemap/Query/
Application/Layout/Value/
```

許可:

```text
Application/Article/Edit/
Application/Auth/ResetLoginAttempt/
Application/Layout/Load/
Application/Sitemap/Build/
```

route-level permission は `Application/Auth/AuthorizeRoute/` という UseCase として扱う。`Policy` は `Application` 配下の中間分類ディレクトリとして作らない。

resource-level permission は Domain rule として `Domain/Policy/` に置く。たとえば Article の対象 resource に対する更新・削除・復元可否は `Application/Article/Policy` ではなく `Domain/Policy/ArticlePolicy` が扱う。

`Service` ディレクトリは例外中の例外である。UseCase private method、UseCase 配下の `Port` / `Query` / `Value`、Domain、Domain Repository のいずれにも置けない場合だけ検討する。作成前に設計判断を明確にする。

`Application` 直下に `ShowTag`、`ListTag`、`CreateTag` のような名前を乱立させてはならない。対象概念でグルーピングする。

禁止:

```text
Application/
  ShowTag/
  ListTag/
  CreateTag/
```

許可:

```text
Application/
  Tag/
    Show/
    List/
    Create/
```

### 5.1 Input

`Input.php` は Controller から Interactor へ渡す DTO である。

`Input` は UseCase 専用型として、必ず `Application/<Feature>/<Action>/Input.php` に置く。複数の UseCase で共有する汎用 `Input`、`SaveInput`、`RequestInput` のような共通 DTO を作ってはならない。

ルール:

- フレームワーク型を持たない
- `Request`、`Response`、`PDO`、`Container`、`Latte` を持たない
- scalar、array、ValueObject は持ってよい
- Controller でリクエストごとに `new` する
- singleton service の property に保持しない
- request 固有の状態を static property、global、shared service に退避しない

`Input` の constructor 呼び出しでは named arguments を使う。

```php
new Input(
    id: $id,
    title: $request->input('title'),
    body: $request->input('body'),
);
```

禁止:

```php
new Input($id, $request->input('title'), $request->input('body'));
```

### 5.2 Interactor

`Interactor.php` はユースケースを実行する。

ルール:

- `Input` を受け取る
- `Output` を返す
- constructor は DI する依存だけを受け取る
- constructor で scalar 設定値、env 値、業務ルールの閾値を受け取らない
- 読み取りでは Application Query interface を呼ぶ
- Entity 永続化では Domain Repository interface を呼ぶ
- Domain object を組み立て、業務ルールを実行する
- Infrastructure class に依存しない
- Template を描画しない
- HTTP Response を作らない

UseCase 固有の小さな閾値や制御値は、まず `Interactor` の private const として置く。環境ごとの差し替えが本当に必要になった場合は、constructor に scalar を増やすのではなく、その UseCase に意味のある設定 Value Object または Policy object を検討する。

禁止:

```php
final readonly class Interactor
{
    public function __construct(
        private ILoginAttemptRepository $loginAttempts,
        private int $windowSeconds,
        private int $maxFailures,
    ) {
    }
}
```

許可:

```php
final readonly class Interactor
{
    private const ATTEMPT_WINDOW_SECONDS = 600;
    private const MAX_FAILURES = 5;

    public function __construct(private ILoginAttemptRepository $loginAttempts)
    {
    }
}
```

### 5.3 Output

`Output.php` は Interactor から返す DTO である。

ルール:

- Controller または Presenter が必要とするデータを持つ
- DB row をそのまま露出しない
- `PDO`、`Request`、`Response`、`Renderer` を持たない

### 5.4 Value

`Value/` は Infrastructure / Application 間のレイヤー越えのための DTO を置く。

```text
Application/<Feature>/<Action>/Value/<Name>.php
```

Query が単一の値を返す場合、PHP 組み込みの `array`、DB row、scalar を直接返してはならない。必ず UseCase 配下の `Value` DTO を返す。

1つの Query interface に対して、用意してよい Value DTO は最大1つとする。

一覧を返す場合は `...List` という Value DTO を作らない。`array<Article>` のように、要素の型を phpdoc で明示した配列を返す。

1つの UseCase が複数種類の projection を必要とする場合は、1つの Query interface に複数 DTO を混在させず、`IArticleQuery` と `ICategoryQuery` のように Query interface を分ける。

例:

```text
Application/Article/List/Query/IArticleQuery.php
Application/Article/List/Value/Article.php
```

```php
interface IArticleQuery
{
    /** @return array<int, Article> */
    public function list(string $keyword = ''): array;
}
```

Application Value はレイヤー越えのための DTO であり、Domain Entity ではない。業務不変条件や状態遷移を持たせてはならない。

## 6. Query ルール

Application Query は、Interactor が利用する読み取り側の port である。

Query interface は、それを必要とするユースケース配下に置く。

```text
Application/<Feature>/<Action>/Query/I<対象>Query.php
```

例:

```text
apps/cms/src/Application/Dashboard/Show/Query/IDashboardQuery.php
apps/cms/src/Application/Inquiry/List/Query/IInquiryQuery.php
apps/front/src/Application/Article/Show/Query/IArticleQuery.php
apps/front/src/Application/Tag/List/Query/ITagQuery.php
```

Query interface は `I` prefix を付け、interface であることを明示する。

```php
interface IArticleQuery
{
    // use case が必要とする読み取りメソッド
}
```

Query implementation は Infrastructure に置く。

```text
apps/front/src/Infrastructure/Persistence/PdoArticleQuery.php
apps/cms/src/Infrastructure/Persistence/PdoDashboardQuery.php
```

`QueryService` という命名は禁止する。

禁止:

```text
ArticleQueryService.php
PdoArticleQueryService.php
Domain/ArticleQueryService.php
```

許可:

```text
Application/Article/Show/Query/IArticleQuery.php
Infrastructure/Persistence/PdoArticleQuery.php
```

Query は UseCase 配下の `Value` DTO、または `array<Article>` のように要素型を明示した Value DTO の配列を返す。DB row、連想配列、scalar を直接返してはならない。

画面表示用 projection のために mutable な Domain Entity を返してはならない。

## 7. Repository ルール

Repository interface は、Domain Entity または Aggregate の永続化と取得のための Domain port である。

```text
Domain/Repository/I<対象>Repository.php
```

例:

```text
apps/cms/src/Domain/Repository/IArticleRepository.php
apps/cms/src/Domain/Repository/IPageRepository.php
apps/front/src/Domain/Repository/IInquiryRepository.php
```

Repository interface は `I` prefix を付け、interface であることを明示する。

```php
interface IArticleRepository
{
    public function save(Article $article): void;
}
```

Repository implementation は Infrastructure に置く。

```text
Infrastructure/Persistence/PdoArticleRepository.php
Infrastructure/Persistence/PdoInquiryRepository.php
```

Repository は画面一覧、検索結果、ダッシュボード、読み取り専用 projection のために作らない。それらは Application Query として扱う。

Repository が値を返す場合、返してよいものは Domain `Entity` または Domain `Value` のみである。PHP 組み込みの `array`、DB row、scalar を直接返してはならない。

Repository は Aggregate または明確な業務単位で作る。テーブル単位で作ってはならない。

禁止:

```text
IArticleTableRepository
IArticleTagTableRepository
IArticleRevisionTableRepository
```

## 8. CQRS ルール

読み取り側:

```text
Presentation Controller
  -> Application/<Feature>/<Action>/Input
  -> Application/<Feature>/<Action>/Interactor
  -> Application/<Feature>/<Action>/Query/I...Query
  -> Infrastructure/Persistence/Pdo...Query
  -> Application/<Feature>/<Action>/Value
```

書き込み側:

```text
Presentation Controller
  -> Application/<Feature>/<Action>/Input
  -> Application/<Feature>/<Action>/Interactor
  -> Domain Entity / ValueObject
  -> Domain/Repository/I...Repository
  -> Infrastructure/Persistence/Pdo...Repository
  -> Domain Entity / Value
```

書き込み側を「Domain Repository が Command を実行する」と表現してはならない。正確には、Application Interactor がユースケースを実行し、その永続化 port として Domain Repository を利用する。

CQRS を理由にトップレベルの `Application/Command` と `Application/Query` を作ってはならない。

禁止:

```text
Application/
  Command/
  Query/
```

許可:

```text
Application/
  Article/
    Save/
      Input.php
      Interactor.php
      Output.php
    List/
      Input.php
      Interactor.php
      Output.php
      Query/
        IArticleQuery.php
```

ユースケース内の `Command/` ディレクトリは、ログ出力や外部コマンドのような非 Domain の副作用 port が必要な場合だけ許可する。標準では作らない。

## 9. Domain ルール

Domain は業務概念と業務ルールを表す。

置いてよいもの:

```text
Entity
Aggregate
ValueObject
Domain Service
Policy
Domain Exception
Repository Interface
```

Domain の `Entity/` には Entity のみを置く。ValueObject / Value は `Domain/Value/` に置く。

Entity が内包する要素は、原則として `Domain/Value` の ValueObject として定義する。Entity は必要な ValueObject を `use` して構成する。

例:

```php
use App\Cms\Domain\Value\ArticleBody;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleSlug;
use App\Cms\Domain\Value\ArticleStatus;
use App\Cms\Domain\Value\ArticleTitle;

final class Article
{
    public function __construct(
        private ?ArticleId $id,
        private ArticleTitle $title,
        private ArticleSlug $slug,
        private ArticleBody $body,
        private ArticleStatus $status,
    ) {
    }
}
```

Entity が `string`、`int`、`array` などの primitive を大量に直接抱える実装は禁止する。

置いてはならないもの:

```text
Application Query Interface
ReadModel
View DTO
Application Value DTO
PDO
SQL
HTTP Request
HTTP Response
Latte
Container
```

Domain Entity は、不変条件、状態遷移、Policy、Aggregate 整合性がある場合に作る。

一覧表示や詳細表示だけのために Domain Entity を作ってはならない。

## 10. Infrastructure ルール

Infrastructure は外部技術の詳細を扱う。

置いてよいもの:

```text
PDO
SQL
Repository implementation
Query implementation
Mapper
Renderer adapter
Filesystem
Logger implementation
Clock implementation
```

Infrastructure は Application と Domain の interface に依存してよい。

Application と Domain は Infrastructure に依存してはならない。

アプリ固有の HTTP middleware は、現時点では `apps/<app>/src/Presentation/Http/Middleware` に置く。HTTP pipeline に差し込まれるが、ログイン状態、route-level permission、redirect、画面向け request 制約など、HTTP request/response の入口制御は Presentation の責務として扱う。

middleware が増え、1つの置き場では責務が読みにくくなった場合だけ、次の基準で分離する。

- `apps/<app>/src/Presentation/Http/Middleware/`: 画面、ルート、ログイン状態、表示モード、SEO、redirect、route-level permission、HTTP envelope audit など、HTTP 表現と入口制御に直接関わるもの。
- `apps/<app>/src/Infrastructure/Http/Middleware/`: proxy header、upload limit、外部基盤都合の request/response 制約など、技術的な入出力や実行基盤に近いものが必要になった場合だけ作る。
- `src/Framework/Http/Middleware/`: app に依存しない middleware interface、pipeline、共通実行機構。

分離する場合は class の namespace、`apps/<app>/config/middleware.php` の import、README の配置説明を同じ変更単位で更新する。

## 10.5 Presentation ルール

Presentation は HTTP/HTML としてどう見せるかを扱う。

置いてよいもの:

```text
HTTP Controller
ViewModel
Presenter
SEO meta
canonical URL
Request / Response DTO
```

Presentation は Application を呼んでよい。Presentation は Infrastructure/Persistence の具象実装へ直接依存してはならない。

Front と CMS の HTTP Controller は `apps/<app>/src/Presentation/Http/Controller` に置く。Front の SEO meta や sitemap などの公開表現も `apps/front/src/Presentation` に置く。

## 11. Controller ルール

Controller は表示層の入口である。Front と CMS のどちらも `Presentation/Http/Controller` に置く。

Controller の責務:

- framework HTTP request を受け取る
- primitive な入力値を取り出す
- Application `Input` を作る
- Application `Interactor` を呼ぶ
- `Output` を HTTP response や template 描画へ変換する

Controller に業務ルール、SQL、永続化処理を書いてはならない。

### 11.1 PRG ルール

CMS の更新系 HTTP 処理は、リロードによる重複登録を避けるため PRG、Post Redirect Get、を原則とする。

ルール:

- `POST` で作成、更新、復元などの変更処理を実行する
- 変更処理が成功したら、必ず `Response::redirect()` で `GET` 画面へ遷移する
- redirect 後の `GET` 画面で flash message を表示する
- validation error など永続化前に失敗した場合は、同一画面を `422` で表示してよい
- 成功した `POST` response で直接 template を描画してはならない

例:

```text
POST /admin/articles
  -> Application/Article/Save/Interactor
  -> 302 Location: /admin/articles/{id}
GET /admin/articles/{id}
  -> Application/Article/Edit/Interactor
  -> template
```

UseCase 名は画面表示なら `Edit`、作成/更新の永続化なら `Save` を使ってよい。HTTP メソッド名に合わせる目的だけで `Post`、`Update` を機械的に増やす必要はない。

更新履歴のプレビューは読み取り処理であり、`Application/ArticleVersion/Preview` として扱う。`ArticleVersion` は `article_versions` の1つの版を表す対象名であり、不自然な短縮名を使ってはならない。

記事と固定ページの履歴は、編集画面で保存できる項目のスナップショットとして扱う。保存時は本体テーブル、履歴テーブル、`current_version_no` を同一トランザクションで更新する。

`article_versions` は記事のタイトル、スラッグ、本文、抜粋、表示設定、カテゴリID、公開日時、公開終了日時、SEOタイトル、meta description を保存する。カテゴリーやタグのマスタ定義、記事とタグの当時の関連付けは履歴復元の対象外とする。

`page_versions` は固定ページのタイトル、スラッグ、本文、状態、表示順、SEOタイトル、meta description を保存する。

公開中固定ページのフロントリンクは、HTMLを直接編集して追加してはならない。Front ApplicationのLayout用Queryで `published` な固定ページを取得し、`display_order` 昇順で共通レイアウトへ渡して表示する。

### 11.2 UseCase import ルール

Controller が Application UseCase を利用する場合、不要な `as` alias を使ってはならない。

単一 UseCase だけを使う Controller では、`Input`、`Interactor`、`Output` をそのまま import する。

```php
use App\Cms\Application\Article\Save\Input;
use App\Cms\Application\Article\Save\Interactor;

final readonly class Controller
{
    public function __construct(private Interactor $save)
    {
    }

    public function __invoke(Request $request): Response
    {
        $output = $this->save->execute(new Input(
            id: null,
            data: $request->body,
        ));

        // ...
    }
}
```

複数 UseCase を同じ Controller で使う場合は、UseCase の1つ上の階層までを `use` し、`Edit\Input`、`History\Input` のように参照する。

```php
use App\Cms\Application\Article\Edit;
use App\Cms\Application\Article\History;

final readonly class Controller
{
    public function __construct(
        private Edit\Interactor $edit,
        private History\Interactor $history,
    ) {
    }

    private function editForm(Edit\Output $output, int $articleId): Response
    {
        $versions = $this->history->execute(new History\Input(
            articleId: $articleId,
        ))->versions;

        // ...
    }
}
```

禁止:

```php
use App\Cms\Application\Article\Save\Input as SaveInput;
use App\Cms\Application\Article\Edit\Input as EditInput;
use App\Cms\Application\Article\History\Input as HistoryInput;
```

`as` alias は、同名 class を同じファイルでどうしても直接 import する必要があり、上位 namespace import では解決できない場合に限る。

## 12. DI ルール

DI はアプリケーションごとに設定する。

```text
apps/front/config/container.php
apps/cms/config/container.php
```

`front` と `cms` の DI 設定を共通化してはならない。

Binding は明示する。

```php
IArticleQuery::class => PdoArticleQuery::class
IArticleRepository::class => PdoArticleRepository::class
```

Domain と Application に Container を inject してはならない。

FrankenPHP Worker mode に移行しても安全に動かせるよう、DI では request 固有の状態を singleton として保持してはならない。

request scope 扱いにするもの:

- `Request`
- `Session`
- `Csrf`
- 現在ログイン中のユーザー
- flash message
- validation error
- request id / request context

singleton にしてよいもの:

- 設定
- stateless な Interactor
- stateless な Query / Repository
- DB / Redis / 外部 API client の factory、または `workerSingleton()` による worker 単位の接続・client 再利用
- template engine 本体

`Renderer` などを singleton にする場合でも、global provider が特定リクエストの `Request`、`Session`、`Csrf` instance を固定保持してはならない。provider 実行時に request scope から取り出す。

通常の service 定義は request ごとに作り直される。`workerSingleton()` は、worker 実行中に使い回したい service だけを明示的に登録するための lifetime 指定である。現在の PHP worker のメモリに instance を保持するため、request 固有状態を登録してはならない。

禁止:

```php
$container->singleton(Csrf::class, fn () => new Csrf($session));
$container->singleton(CurrentUser::class, fn () => $userFromCurrentRequest);
```

許可:

```php
$requestScope->instance(Request::class, $request);
$requestScope->instance(Session::class, $session);
$requestScope->set(Csrf::class, fn (Container $container): Csrf => new Csrf(
    session: $container->get(Session::class),
));
```

## 13. PHP 呼び出しルール

### 13.1 Named arguments

プロジェクト内 class の object constructor、method、function 呼び出しでは、引数が2つ以上ある場合は原則として named arguments を使う。

`apps/cms`、`apps/front` の Application / Presentation では、引数が1つだけで、method 名や渡す値から意味が明白な場合は positional arguments を使ってよい。

Framework / Kernel などの基盤層や、抽象的な public API では、引数が1つだけでも named arguments を優先する。`get(id: ...)`、`input(key: ...)`、`handle(request: ...)` のように method 名だけでは引数の意味が抽象的な呼び出しでは、引数名が読みやすさを補うためである。

Application / Presentation で同じように抽象的な single argument API が増える場合は、named arguments で補う前に method 名、責務、配置を見直す。業務層の呼び出しは、原則として `findByEmail($email)`、`delete($id)`、`execute(new Input(...))` のように、呼び出し名と渡す値から意味が読める形を優先する。

抽象的かどうかは固定リストで判定しない。判断に迷う場合は、次の観点でその都度判断する。

- method 名だけで引数の役割が読めるか
- 引数名を外しても、型と変数名から意味が落ちないか
- 業務語彙ではなく `get`、`set`、`handle`、`run`、`input`、`process` のような基盤語彙だけで成り立っていないか
- 同じ型の引数が増えた時に、呼び出し側が破綻しやすくないか

private method の呼び出しでは、単一引数の named arguments は原則不要である。private method は同一 class 内に閉じており、定義へジャンプすれば意図と型をすぐ確認できるためである。

目的:

- 引数順の読み違いを防ぐ
- DTO / ValueObject の追加変更に強くする
- UseCase `Input` の意味を Controller から読み取れるようにする

許可:

```php
new Response(
    body: $html,
    status: 422,
);

new Article(
    id: $id,
    title: $title,
    slug: $slug,
    body: $body,
);
```

禁止:

```php
new Response($html, 422);
new Article($id, $title, $slug, $body);
```

単一引数で許可:

```php
$user = $this->users->findByEmail($input->email);

if ($this->hasTooManyFailedLoginAttempts($input, $now)) {
    return Output::blocked();
}

$output = $this->save->execute(new Input(
    data: $request->body,
));

$secureCookie = $this->secureCookie($secure);
```

単一引数でも named arguments を優先:

```php
$value = $request->input(key: 'email');
$service = $container->get(id: UserRepository::class);

return $handler->handle(request: $request);
```

例外:

- PHP 標準関数のように named arguments がかえって読みにくい呼び出し
- 外部ライブラリで引数名の後方互換性が保証されない public API
- `sprintf`、`in_array` など短く慣用的な関数
- 引数がない呼び出し
- `apps/cms`、`apps/front` の Application / Presentation で、引数が1つだけで意味が明白な呼び出し
- private method の単一引数呼び出し

UseCase `Input` の constructor 呼び出しでは、引数数に関わらず named arguments を使う。ただし `execute(new Input(...))` のように、`Input` object 自体を唯一の引数として渡す呼び出しでは `execute` 側の `input:` は省略してよい。

例外に該当しないプロジェクト内 class の constructor / method では named arguments を優先する。

### 13.2 配列処理

本プロジェクトでは、callback を受け取る配列処理関数を原則として使わない。

対象:

- `array_map`
- `array_filter`
- `array_reduce`
- `array_walk`

理由:

- PHP は callback の純粋性を言語レベルで保証しない
- callback は外側の変数、object、static、global、DB、file、session、log へ副作用を持てる
- `array_reduce` は accumulator の型が読みにくく、PHPStan でも型が崩れやすい
- DTO 生成、DB row 変換、`toArray()` 変換では named arguments と相性が悪くなりやすい
- パフォーマンス面でも `foreach` の方が余計な callback 呼び出しを避けられる

禁止:

```php
return array_map(fn (array $row): Article => new Article(
    id: (int) $row['id'],
    title: (string) $row['title'],
), $rows);

$published = array_filter($articles, fn (Article $article): bool => $article->isPublished());

$grouped = array_reduce($rows, function (array $carry, array $row): array {
    $carry[(string) $row['category']][] = $row;
    return $carry;
}, []);
```

許可:

```php
$articles = [];
foreach ($rows as $row) {
    $articles[] = new Article(
        id: (int) $row['id'],
        title: (string) $row['title'],
    );
}

$published = [];
foreach ($articles as $article) {
    if (!$article->isPublished()) {
        continue;
    }

    $published[] = $article;
}

$grouped = [];
foreach ($rows as $row) {
    $grouped[(string) $row['category']][] = $row;
}
```

callback を受け取らず、操作の意味が明確な PHP 標準配列関数は許可する。

許可例:

- `array_values`
- `array_keys`
- `array_column`
- `array_slice`
- `array_reverse`
- `array_shift`
- `array_pop`
- `count`
- `in_array`

ただし、`array_shift` と `array_pop` は配列を破壊的に変更する。破壊的操作が意図として明確な場合に限って使う。

例外として、外部ライブラリが callback 形式の API を要求する場合は許可する。その場合でも、callback 内で副作用を起こしてはならない。

## 14. Namespace ルール

Composer PSR-4 root:

```json
{
  "autoload": {
    "psr-4": {
      "App\\Framework\\": "src/Framework/",
      "App\\Kernel\\": "apps/kernel/src/",
      "App\\Shared\\": "apps/shared/src/",
      "App\\Front\\": "apps/front/src/",
      "App\\Cms\\": "apps/cms/src/"
    }
  }
}
```

例:

```php
namespace App\Front\Application\Article\Show;
namespace App\Front\Application\Article\Show\Query;
namespace App\Front\Domain\Repository;
namespace App\Front\Infrastructure\Persistence;

namespace App\Kernel\Database;
namespace App\Shared\View\Infrastructure;
namespace App\Shared\Markdown\Infrastructure;

namespace App\Cms\Application\Dashboard\Show;
namespace App\Cms\Application\Dashboard\Show\Query;
namespace App\Cms\Domain\Repository;
namespace App\Cms\Infrastructure\Persistence;
```

禁止 namespace:

```php
namespace App\Common;
namespace App\Core;
namespace App\Utils;
namespace App\Front\WebMedia;
namespace App\Cms\WebMedia;
```

## 15. 命名規則

### 15.1 UseCase / Application

UseCase file:

```text
Input.php
Interactor.php
Output.php
```

Query interface:

```text
I<対象>Query.php
```

Repository interface:

```text
I<対象>Repository.php
```

PDO Query implementation:

```text
Pdo<対象>Query.php
```

PDO Repository implementation:

```text
Pdo<対象>Repository.php
```

禁止する曖昧な名前:

```text
Manager
Helper
Util
Common
Base
Core
Service
Processor
Handler
```

本プロジェクトでは UseCase に `Handler` を使わない。`Interactor` を使う。

### 15.2 Controller

Controller は HTTP アクション単位のディレクトリに置く。

```text
Presentation/Http/Controller/<対象>/<HTTPアクション>/Controller.php
```

Front 例:

```text
Presentation/Http/Controller/Article/List/Controller.php
Presentation/Http/Controller/Article/Show/Controller.php
Presentation/Http/Controller/Search/Index/Controller.php
```

CMS 例:

```text
Presentation/Http/Controller/Article/List/Controller.php
Presentation/Http/Controller/Article/Create/Controller.php
Presentation/Http/Controller/Article/Store/Controller.php
```

Controller class 名は各ディレクトリ内で `Controller` とする。

```php
namespace App\Front\Presentation\Http\Controller\Article\Show;

final readonly class Controller
{
}
```

`ArticleStoreController`、`StoreArticleController` のようにアクション名と対象名を class 名へ詰め込まない。namespace と path で対象とアクションを表現する。

### 15.3 HTTP アクション名

HTTP Controller のアクション名は、画面と HTTP の意味を優先する。

```text
List    一覧表示
Show    詳細表示
Create  新規作成フォーム表示
Store   新規作成 POST
Edit    編集フォーム表示
Update  更新 POST
Delete  削除 POST
Restore 復元 POST
Preview プレビュー表示
History 履歴表示
```

`Create` と `Store`、`Edit` と `Update` は分ける。

```text
GET  /admin/articles/create -> Article/Create/Controller.php
POST /admin/articles        -> Article/Store/Controller.php
GET  /admin/articles/{id}   -> Article/Edit/Controller.php
POST /admin/articles/{id}   -> Article/Update/Controller.php
```

読み取り画面は `Show`、一覧は `List` を使う。`Index` は原則使わない。ただし、検索画面など対象概念に対して「一覧」より入口画面の意味が強い場合は `Index` を許可する。

Application UseCase 名は HTTP アクション名と必ず一致させる必要はない。

例:

```text
Article/Store/Controller.php
  -> Application/Article/Save/Interactor

Article/Update/Controller.php
  -> Application/Article/Save/Interactor
```

HTTP アクションは「リクエストの入口」を表し、Application UseCase は「業務操作」を表す。

### 15.4 Request / Response DTO

Controller アクション配下には、必要になった時だけ `Request.php` や `Response.php` を追加してよい。

```text
Presentation/Http/Controller/Article/Show/
  Controller.php
  Request.php
  Response.php
```

`Request.php` は HTTP 入力の取り出し、型変換、軽い形式検証を担当する。

`Response.php` は template 変数の組み立て、redirect 先、HTTP status などの変換が複雑になった場合に使う。

禁止:

```text
全アクションに空の Request.php / Response.php を機械的に作る
Request.php に業務ルールを書く
Response.php に Application の実行処理を書く
```

Controller が十分薄い場合は `Controller.php` だけでよい。

### 15.5 Middleware / Guard

CMS の認証チェックは、将来的に middleware または guard へ寄せる。

`middleware` は HTTP request が Controller に到達する前後で処理する仕組みである。ルート単位の認証、認可、CSRF、request id、logging などに向いている。

`guard` は「現在のリクエストが認証済みか」「現在のユーザーは誰か」を判定する認証境界である。middleware から guard を呼ぶ構成にしてよい。

例:

```text
cms.auth middleware
  -> CmsGuard
      -> Session / CurrentUser
```

Controller ごとに `Session` を直接読んでログイン判定を繰り返してはならない。移行途中は許容するが、最終的には `cms.auth` middleware へ寄せる。

### 15.6 Middleware で扱ってよいこと、扱ってはいけないこと

Middleware は HTTP pipeline の入口制御と横断処理を担当する。コードを差し込める場所ではあるが、対象リソースの業務判断を置く場所ではない。

扱ってよいこと:

- ログイン済みかどうかを確認する。
- route metadata に書かれた粗い permission を確認する。
- CSRF token、request method、path prefix のような HTTP request envelope を確認する。
- request id、IP address、user-agent など、HTTP request metadata をログ用 event に載せる。
- controller 実行前後で、HTTP envelope としての audit / security event を発火する。

扱ってはいけないこと:

- route variables の `{id}` などを読んで、対象記事や対象ページの所有者判定をする。
- `article_id` から DB を引き、writer が自分の記事だけ編集できるかを middleware で判断する。
- 記事の公開状態、予約状態、ゴミ箱状態、復元可否など、対象リソースの状態に依存する業務判断をする。
- route path や route parameter をもとに UseCase の代替処理を組み立てる。

理由:

- route-level permission は「この入口に入ってよいか」の粗い判定であり、「この対象を操作してよいか」の判定ではない。
- 対象リソースに依存する判定を middleware に置くと、Controller / UseCase / Policy との責務境界が曖昧になる。
- HTTP 以外、たとえば CLI、batch、将来の API から同じ UseCase を呼ぶ場合に、middleware の判定が通らない。
- route variables は URL 表現であり、業務上の対象リソースそのものではない。

それをしたくなった場合:

- まず UseCase `Input` に必要な id と `CurrentCmsUser` を渡す。
- UseCase 内で対象リソースの Domain Policy を呼ぶ。
- 対象リソースの所有者、状態、公開可否、復元可否などは Policy または Domain / Application の業務ルールとして判定する。
- middleware には、必要なら `requires_permission` のような route metadata による粗い入口制御だけを残す。

例:

```text
RequireCmsPermissionMiddleware
  -> article.update permission があるかだけを見る

Article/Save/Interactor
  -> ArticlePolicy で author_id、status、actor role を見て更新可否を判定する
```

## 16. CMS と Front のモデル分離

CMS の記事と Front の記事は同じ application model として扱わない。

CMS は編集上の関心を扱う。

```text
draft
published visibility
private visibility
publication period
history
revision
editor
internal memo
```

Front は公開済みコンテンツ projection を扱う。

```text
public title
public body
published date
published ends date
slug
category
tag
SEO
related articles
```

記事の公開状態は `status` だけで表現しない。

- `published`: 表示対象
- `private`: 非表示
- `draft`: 下書き
- `trashed`: ゴミ箱

`公開中`、`公開予約`、`公開終了` は保存ステータスではなく、`status = published` と `published_at` / `published_ends_at` から導出する表示状態である。CMS の記事編集画面で `公開予約` をステータスとして選択させてはならない。

禁止:

```text
front が cms の Article Entity を使う
cms の編集用 Article Entity を front template に渡す
front の Article Output を cms の編集に使う
cms/front 共通の Article Entity を作る
```

## 17. Persistence ルール

DB アクセスには PDO を使う。

PDO と SQL は Infrastructure でのみ許可する。

Infrastructure 以外で禁止:

```php
new PDO(...);
$pdo->query(...);
$pdo->prepare(...);
```

DB row から Domain object、Domain Value、Application Value DTO への変換は Infrastructure の責務であり、mapper method または mapper class で扱う。

DB row を Query / Repository の戻り値としてそのまま返してはならない。

## 18. Test ルール

テスト対象:

```text
Domain Entity / ValueObject
Application Interactor
Infrastructure PDO Repository
Infrastructure PDO Query
Presentation HTTP Controller integration
```

Interactor の unit test では fake Query または fake Repository implementation を使う。

PDO test では test database を使ってよい。

## 19. 実装チェックリスト

実装完了前に必ず確認する。

```text
[ ] cms が front に依存していない
[ ] front が cms に依存していない
[ ] apps/shared を雑多な helper や業務ロジックの避難場所にしていない
[ ] apps/common、apps/modules、apps/framework を作っていない
[ ] Interface は依存制御のためだけに導入している
[ ] Query interface は Application/<Feature>/<Action>/Query 配下にある
[ ] Application/<Feature>/ 直下の通常ディレクトリは Action であり、Input / Interactor / Output を持つ
[ ] Application 配下に Policy ディレクトリを作っていない
[ ] Service ディレクトリを作る場合は、他に置けない理由を明確にしている
[ ] Query interface は I prefix を使っている
[ ] Query は PHP array ではなく Application Value DTO を返している
[ ] 1つの Query interface に対して Value DTO は最大1つである
[ ] Query implementation は Infrastructure/Persistence 配下にある
[ ] Domain Repository interface は Domain/Repository 配下にある
[ ] Repository interface は I prefix を使っている
[ ] Repository の戻り値は Domain Entity または Domain Value である
[ ] Repository implementation は Infrastructure/Persistence 配下にある
[ ] Application use case は Input、Interactor、Output を持つ
[ ] Application Input は UseCase 専用型であり、共通 Input DTO を使っていない
[ ] UseCase Input の constructor 呼び出しは引数数に関わらず named arguments を使っている
[ ] Application は Infrastructure に依存していない
[ ] Domain は Application / Infrastructure に依存していない
[ ] Presentation は Infrastructure/Persistence の具象実装に直接依存していない
[ ] Controller に業務ルールを書いていない
[ ] Controller の UseCase import で不要な `as` alias を使っていない
[ ] 複数 UseCase を使う Controller は上位 namespace import と `Edit\Input` 形式で参照している
[ ] `array_map` / `array_filter` / `array_reduce` / `array_walk` を使っていない
[ ] PDO と SQL が Infrastructure 以外に出ていない
[ ] QueryService 命名を使っていない
[ ] UseCase に Handler 命名を使っていない
[ ] namespace と path が一致している
[ ] DI binding が明示されている
[ ] request 固有の状態を singleton service に保持していない
```

## 20. 最終原則

ファイル数削減を目的に責務を混ぜてはならない。

薄いクラスであっても、責務が明確なら許可する。

誤り:

```text
Controller が use case を実行する
Interactor が SQL を実行する
Repository が画面 projection を返す
Domain が Query interface を持つ
Application が PDO を持つ
QueryService 命名で port の意図を曖昧にする
Query / Repository が PHP array を返す
```

正しい:

```text
Controller が Input を作る
Interactor が use case を実行する
Query が use case の projection を読む
Query が Application Value DTO を返す
Repository が Domain Entity を永続化する
Repository が Domain Entity / Value を返す
Infrastructure が port を実装する
```
