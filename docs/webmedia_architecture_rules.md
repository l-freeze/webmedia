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

このプロジェクト自体が WEB メディアである。そのため、`WebMedia`、`Shared`、`Common`、`Core`、`Utils`、`Helpers` のような曖昧なアプリケーションディレクトリを作ってはならない。

`front` と `cms` の両方から使う技術基盤は `src/Framework` に置く。

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

`apps/common`、`apps/shared`、`apps/modules`、`apps/framework` は禁止する。`apps` は実行アプリケーションコンテキストの置き場である。

## 3. 依存ルール

依存は内側へ向ける。

許可:

```text
Controller -> Application Interactor
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

## 4. ディレクトリ構造

### 4.1 ルート

```text
webmedia/
  apps/
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
    Infrastructure/
      Http/
        Controller/
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
    Infrastructure/
      Http/
        AuthGuard.php
        Controller/
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
Application/<対象>/<ユースケース>/
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

ルール:

- フレームワーク型を持たない
- `Request`、`Response`、`PDO`、`Container`、`Latte` を持たない
- scalar、array、ValueObject は持ってよい

### 5.2 Interactor

`Interactor.php` はユースケースを実行する。

ルール:

- `Input` を受け取る
- `Output` を返す
- 読み取りでは Application Query interface を呼ぶ
- Entity 永続化では Domain Repository interface を呼ぶ
- Domain object を組み立て、業務ルールを実行する
- Infrastructure class に依存しない
- Template を描画しない
- HTTP Response を作らない

### 5.3 Output

`Output.php` は Interactor から返す DTO である。

ルール:

- Controller または Presenter が必要とするデータを持つ
- DB row をそのまま露出しない
- `PDO`、`Request`、`Response`、`Renderer` を持たない

### 5.4 Value

`Value/` は Infrastructure / Application 間のレイヤー越えのための DTO を置く。

```text
Application/<対象>/<ユースケース>/Value/<Name>.php
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
Application/<対象>/<ユースケース>/Query/I<対象>Query.php
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
Controller
  -> Application/<対象>/<ユースケース>/Input
  -> Application/<対象>/<ユースケース>/Interactor
  -> Application/<対象>/<ユースケース>/Query/I...Query
  -> Infrastructure/Persistence/Pdo...Query
  -> Application/<対象>/<ユースケース>/Value
```

書き込み側:

```text
Controller
  -> Application/<対象>/<ユースケース>/Input
  -> Application/<対象>/<ユースケース>/Interactor
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
use App\Cms\Domain\Value\ArticleBodyMarkdown;
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
        private ArticleBodyMarkdown $bodyMarkdown,
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
HTTP Controller
Middleware
PDO
SQL
Repository implementation
Query implementation
Mapper
Presenter
Renderer adapter
Filesystem
Logger implementation
Clock implementation
```

Infrastructure は Application と Domain の interface に依存してよい。

Application と Domain は Infrastructure に依存してはならない。

## 11. Controller ルール

Controller は Infrastructure に置く。

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

## 13. Namespace ルール

Composer PSR-4 root:

```json
{
  "autoload": {
    "psr-4": {
      "App\\Framework\\": "src/Framework/",
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

namespace App\Cms\Application\Dashboard\Show;
namespace App\Cms\Application\Dashboard\Show\Query;
namespace App\Cms\Domain\Repository;
namespace App\Cms\Infrastructure\Persistence;
```

禁止 namespace:

```php
namespace App\Shared;
namespace App\Common;
namespace App\Core;
namespace App\Utils;
namespace App\Front\WebMedia;
namespace App\Cms\WebMedia;
```

## 14. 命名規則

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

## 15. CMS と Front のモデル分離

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

## 16. Persistence ルール

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

## 17. Test ルール

テスト対象:

```text
Domain Entity / ValueObject
Application Interactor
Infrastructure PDO Repository
Infrastructure PDO Query
HTTP Controller integration
```

Interactor の unit test では fake Query または fake Repository implementation を使う。

PDO test では test database を使ってよい。

## 18. 実装チェックリスト

実装完了前に必ず確認する。

```text
[ ] cms が front に依存していない
[ ] front が cms に依存していない
[ ] apps/shared、apps/common、apps/modules、apps/framework を作っていない
[ ] Query interface は Application/<対象>/<ユースケース>/Query 配下にある
[ ] Query interface は I prefix を使っている
[ ] Query は PHP array ではなく Application Value DTO を返している
[ ] 1つの Query interface に対して Value DTO は最大1つである
[ ] Query implementation は Infrastructure/Persistence 配下にある
[ ] Domain Repository interface は Domain/Repository 配下にある
[ ] Repository interface は I prefix を使っている
[ ] Repository の戻り値は Domain Entity または Domain Value である
[ ] Repository implementation は Infrastructure/Persistence 配下にある
[ ] Application use case は Input、Interactor、Output を持つ
[ ] Application は Infrastructure に依存していない
[ ] Domain は Application / Infrastructure に依存していない
[ ] Controller に業務ルールを書いていない
[ ] PDO と SQL が Infrastructure 以外に出ていない
[ ] QueryService 命名を使っていない
[ ] UseCase に Handler 命名を使っていない
[ ] namespace と path が一致している
[ ] DI binding が明示されている
```

## 19. 最終原則

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
