# Framework refinement considerations

この文書は、Framework の思想を崩さずに洗練させるための検討事項を残すためのもの。

目的は機能を増やすことではなく、Framework が強制すべき構造と、アプリケーション開発者に委ねる領域を明確にすること。

## 基本方針

Framework が担うべきものは、アプリケーション開発者の好みや思想ではなく、安全に開発するための構造である。

- HTTP 入出力を安全な値として扱う
- Runtime だけが副作用を持つ
- Kernel が構成を担当する
- Shared は再利用可能なアプリケーション側部品に留める
- App の設計思想は scaffold で選択肢として提示する
- Framework 本体は UseCase、DTO、Result 型、ORM、認証方式などを強制しない

構造による制約は必要だが、人の設計判断や表現を捻じ曲げるような制約は Framework 本体に載せない。

## Request / Response の永続値化

`Request` と `Response` は、Framework が提供する最も重要な HTTP 値である。

現在の `final readonly` という方向性は維持しつつ、値を変更するのではなく、新しい値を返す操作を増やすことを検討する。

候補:

- `Request::withAttribute()`
- `Request::attribute()`
- `Response::withStatus()`
- `Response::withBody()`
- `Response::withHeaders()`
- `Response::withoutHeader()`
- `Response::json()`
- `Response::html()`
- `Response::text()`

これは PSR-7 に寄せるためではない。HTTP 値オブジェクトとして、状態変更を明示的な変換にするためである。

## Request attributes

Route params や middleware が付与する情報の置き場は、早い段階で明確にした方がよい。

`query`、`body`、`server`、`files` に派生情報を混ぜると、Request の意味が曖昧になる。Route params や middleware context は `attributes` として分離する方が自然である。

例:

```php
$request = $request->withAttribute('route.id', $id);
$id = $request->attribute('route.id');
```

これは開発者のアプリケーション設計を縛るものではなく、Framework 内の HTTP 境界を明確にするための制約である。

## Container の freeze

Rust や F# 的な安全性を PHP Framework に取り込むなら、Container の mutable 期間を限定することは有効である。

検討する制約:

- Kernel composition 中は service registration を許可する
- Application 起動後は Container を変更不可にする
- 解決中の再登録を禁止する
- 未定義依存は即時例外にする

これは開発者の自由を奪う制約ではなく、実行時の不確実性を減らす制約である。

## Scaffold の説明

`minimal` と `layered` は、正解と不正解ではなく、開発者の判断に応じた入口として扱う。

`minimal`:

- 自分の設計で始めたい人向け
- layered が重すぎる人向け
- simple PHP から始めたい人向け
- アーキテクチャーに詳しくない上級者が、独自設計で組み立てる入口

`layered`:

- I/O、Interactor、境界、レイヤー、型定義を最初から置きたい人向け
- 後から全体に型や境界を足すコストを避けたい人向け
- アプリケーションの拡張や保守のために、初期段階で構造コストを払いたい人向け

重要なのは、「layered が正しい」と押し付けることではない。判断材料として、後から境界や型を追加するコストを明示すること。

## Non-goals

Framework の薄さを「未実装」ではなく「意図的な非所有」として伝えるため、README などに Non-goals を明記することを検討する。

Framework が強制しないもの:

- PSR-7 Request / Response
- ORM
- 認証方式
- 認可方式
- UseCase の形
- DTO の形
- Result 型
- Validation の流儀
- Template engine の選択
- Database engine の選択
- アプリケーション層の命名規則

Framework はアプリケーションの作法を所有しない。所有するのは、HTTP 実行基盤、構成境界、副作用境界、そして最低限の安全な接続点である。

## 優先度

現時点で効果が大きい検討事項:

1. `Request attributes` の導入
2. `Response with*` 系操作の充実
3. `Container freeze` の導入
4. Scaffold README の選択基準化
5. Framework README への Non-goals 明記

逆に、UseCase パターン、Result 型、独自 DTO 規約、独自 Validation 思想などを Framework 本体に入れることは、現時点では避ける。

それらは scaffold の例や App 側の選択肢として提示できるが、Framework の制約としては強すぎる。
