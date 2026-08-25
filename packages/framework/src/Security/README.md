# Security

## このディレクトリの責務

`Pera\Framework\Security` は、PHP Web application が安全に使い始めるための小さな security primitive を提供します。

- request scope の `Session`
- post-redirect-get で使う flash value
- CSRF token の生成と検証
- unsafe HTTP method を検証する `CsrfMiddleware`
- native PHP Session の開始、ID再生成、終了、破棄

Framework は認証方式、権限、application固有のSession key、Session保存先を決めません。認証と権限は各application、native Sessionの保存先はPHPの `session.save_handler` とその設定が所有します。

## 最短のSession利用

Sessionを使うControllerだけ、constructorで `Session` を受け取って読み書きします。routeやKernelにSession有効フラグを追加する必要はありません。

```php
<?php

declare(strict_types=1);

namespace App\Site\Presentation\Http\Controller\SpecialPage;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(private Session $session)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $value = $this->session->get(
            key: 'site.special_page.selected_tab',
            default: 'overview',
        );
        $selectedTab = is_string($value) ? $value : 'overview';

        return new Response(body: 'Selected tab: ' . $selectedTab);
    }
}
```

POSTなどの更新処理では、同じkeyへ保存してredirectできます。

```php
$this->session->set(
    key: 'site.special_page.selected_tab',
    value: 'history',
);

return Response::redirect('/special-page');
```

`Session` を注入しただけではnative PHP Sessionは開始しません。`get()`、`set()`、`has()`、`remove()`、flash操作、`regenerate()`、`destroy()`のいずれかを最初に呼んだ時点で開始します。

したがって、同じapplication内にSessionを使うpageと使わないpageが混在しても、path一覧、route metadata、専用middlewareで開始対象を管理する必要はありません。コードがSession stateを必要とした事実そのものが開始条件です。

## Pageや機能がSession keyを所有する

小さなControllerでは `Session` を直接使えます。keyが複数になったり、複数Controllerから同じstateを扱ったりする場合は、application側に用途を表すclassを置きます。

```php
<?php

declare(strict_types=1);

namespace App\Site\Presentation\Session;

use Pera\Framework\Security\Session;

final readonly class SpecialPageSession
{
    private const OPTIONS_KEY = 'site.special_page.options';

    public function __construct(private Session $session)
    {
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        $value = $this->session->get(
            key: self::OPTIONS_KEY,
            default: [],
        );

        return is_array($value) ? $value : [];
    }

    /** @param array<string, mixed> $options */
    public function save(array $options): void
    {
        $this->session->set(key: self::OPTIONS_KEY, value: $options);
    }

    public function clear(): void
    {
        $this->session->remove(key: self::OPTIONS_KEY);
    }
}
```

このclassはFrameworkの拡張ではなく、`site.special_page`というapplication stateの所有者です。Session keyをFramework、Kernel、Domainへ移さず、利用するapplicationのPresentationまたは認証adapterの近くに置きます。Domain EntityやApplication Interactorには `Session` を渡さず、必要な値だけをUseCase Inputへ変換します。

Sessionにはscalar、ID、enumのvalue、必要最小限の配列を保存します。Request、service、PDO、Domain Entityのようなobject graphを保存すると、serialization、deploy、変更容易性の境界が崩れるため避けます。

通常の「page専用Session」は、この例のように同じユーザーSession内でkeyの名前空間を分けることを意味します。pageごとに `session_name()`、Cookie、Session IDを変えると、認証、CSRF、flash、画面遷移が別境界になります。本当に別のsecurity boundaryが必要な場合は、Controller内でnative Session設定を切り替えず、別applicationやhostとして設計します。

## Flash value

redirect後の1回だけ表示する値にはflashを使います。

```php
$this->session->flash(key: 'profile.success', value: '保存しました。');

return Response::redirect('/profile');
```

redirect先で取得すると、そのkeyはflash領域から削除されます。

```php
$message = $this->session->pullFlash(key: 'profile.success');
```

配列などstring以外の値が必要な場合だけ、`flashValue()` と `pullFlashValue()` を使います。

## CSRF

HTML formでは `Csrf` からtokenを取得します。token取得はSession stateを使うため、そのpageをstatefulにします。

```php
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;

final readonly class Controller
{
    public function __construct(private Csrf $csrf)
    {
    }

    public function __invoke(): Response
    {
        return new Response(body: '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8')
            . '">');
    }
}
```

`CsrfMiddleware` はPOST、PUT、PATCH、DELETEを検証します。GET、HEADなど検証対象でないrequestは、middlewareを通過するだけではSessionを開始しません。

## Lifecycleとworker mode

標準Kernelとの境界は固定です。

1. Kernelがrequestごとに新しい `Session` をContainerへ登録する。
2. Application codeが初めてstateへアクセスしたとき、Frameworkがnative PHP Sessionを開始する。
3. Kernelがrequest処理の `finally` でSessionをcloseする。

Session backendがlockを使う場合、そのlockは最初のstate accessからKernelのcloseまで保持されます。PHP標準のfiles handlerはこの対象です。長時間処理を伴うendpointでは、Session accessを必要以上に早く行わないようにします。Sessionを使わないrequestはbackendへ接続せず、lockも取得しません。

FrankenPHP worker modeでも、`Session`、`Request`、`Csrf`、現在ユーザーを `Container::workerSingleton()`、static property、worker lifetime objectへ保存してはいけません。Frameworkが使う `$_SESSION` への参照はrequest scopeの `Session` 内だけに閉じています。application codeは `$_SESSION`、`session_start()`、`session_write_close()`を直接操作せず、`Session` APIを使います。

Frameworkはfile、Redis、databaseなどのSession backendを列挙しません。backendの選択と接続設定はPHP runtimeが所有し、Frameworkはnative Session lifecycleだけを扱います。

`session_start()`、ID再生成、write close、destroyのいずれかが失敗した場合、Frameworkは`RuntimeException`を送出します。失敗した操作を開始済み、保存済み、破棄済みとして扱わず、Controllerやmiddlewareもこの例外を正常系へ握りつぶさないでください。

## 認証時のID再生成

ログイン成功後は、認証情報を保存する前にSession IDを再生成します。

```php
$this->session->regenerate();
$this->session->set(key: 'cms.user_id', value: $userId);
```

logoutでSession全体を終了する場合は `destroy()` を使います。一機能のstateだけを消す場合は、他機能のSessionまで破棄せず `remove()` を使います。

## Test

Controllerやapplication-owned Session classのunit testでは、native Sessionを開始せずin-memory stateを渡せます。

```php
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;

$data = [];
$session = new Session(
    state: new SessionState(data: $data),
    runtime: new NativeSessionRuntime(),
);
```

操作後は `$data` を直接assertできます。native cookie、Session ID、開始とcloseの結合を確認するときだけ、FrameworkまたはKernelのintegration testを使います。

## Scaffold方針

Kernel scaffoldはrequest scopeの `Session` を最初から合成します。一方、app scaffoldはSessionを使うControllerを既定では生成しません。生成直後のGETをstatelessに保ち、公開page、API、health checkなどSession不要のapplicationにも同じ入口を提供するためです。

Sessionが必要になった時点で、上の最短例のようにControllerへ型を1つ追加すれば利用できます。新しい設定、provider、Session backend abstractionは不要です。
