<?php

declare(strict_types=1);

namespace Tests\Kernel;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;
use App\Kernel\Application;
use App\Kernel\ApplicationDefinition;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');
        $_SESSION = [];
    }

    public function testSelectsApplicationByLongestPathPrefix(): void
    {
        $root = sys_get_temp_dir() . '/webmedia-kernel-test-' . bin2hex(random_bytes(4));
        $this->writeApplicationConfig(root: $root, app: 'front', routePath: '/api/status', controller: FrontController::class);
        $this->writeApplicationConfig(root: $root, app: 'api', routePath: '/api/status', controller: ApiController::class);

        $application = new Application(
            root: $root,
            definitions: [
                new ApplicationDefinition(
                    name: 'front',
                    pathPrefix: '/',
                    routesPath: '/apps/front/config/routes.php',
                    containerPath: '/apps/front/config/container.php',
                    middlewarePath: '/apps/front/config/middleware.php',
                ),
                new ApplicationDefinition(
                    name: 'api',
                    pathPrefix: '/api',
                    routesPath: '/apps/api/config/routes.php',
                    containerPath: '/apps/api/config/container.php',
                    middlewarePath: '/apps/api/config/middleware.php',
                ),
            ],
        );

        $response = $application->handle(
            request: new Request(method: 'GET', path: '/api/status', query: [], body: [], server: []),
            session: $this->session(),
        );

        self::assertSame('api', $response->body);
    }

    public function testHandleGlobalsDoesNotStartSessionForStatelessController(): void
    {
        $root = sys_get_temp_dir() . '/webmedia-kernel-stateless-test-' . bin2hex(random_bytes(4));

        try {
            $this->writeApplicationConfig(
                root: $root,
                app: 'front',
                routePath: '/articles/example',
                controller: FrontController::class,
            );
            $application = new Application(
                root: $root,
                definitions: [
                    new ApplicationDefinition(
                        name: 'front',
                        pathPrefix: '/',
                        routesPath: '/apps/front/config/routes.php',
                        containerPath: '/apps/front/config/container.php',
                        middlewarePath: '/apps/front/config/middleware.php',
                    ),
                ],
            );
            $this->resetNativeSession();
            $this->setRequestGlobals(path: '/articles/example');

            $body = $this->handleGlobals(application: $application);

            self::assertSame('front', $body);
            self::assertSame(PHP_SESSION_NONE, session_status());
            self::assertSame('', session_id());
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    public function testStatefulThenStatelessWorkerRequestsDoNotStartSecondSession(): void
    {
        $root = sys_get_temp_dir() . '/webmedia-kernel-worker-session-test-' . bin2hex(random_bytes(4));

        try {
            $this->writeApplicationConfig(
                root: $root,
                app: 'stateful',
                routePath: '/state',
                controller: StatefulController::class,
            );
            $this->writeApplicationConfig(
                root: $root,
                app: 'front',
                routePath: '/articles/example',
                controller: FrontController::class,
            );
            $application = new Application(
                root: $root,
                definitions: [
                    new ApplicationDefinition(
                        name: 'stateful',
                        pathPrefix: '/state',
                        routesPath: '/apps/stateful/config/routes.php',
                        containerPath: '/apps/stateful/config/container.php',
                        middlewarePath: '/apps/stateful/config/middleware.php',
                    ),
                    new ApplicationDefinition(
                        name: 'front',
                        pathPrefix: '/',
                        routesPath: '/apps/front/config/routes.php',
                        containerPath: '/apps/front/config/container.php',
                        middlewarePath: '/apps/front/config/middleware.php',
                    ),
                ],
            );
            $this->resetNativeSession();
            $this->setRequestGlobals(path: '/state');

            self::assertSame('stateful', $this->handleGlobals(application: $application));
            self::assertSame(PHP_SESSION_NONE, session_status());
            self::assertNotSame('', session_id());
            self::assertTrue($_SESSION['visited'] ?? false);

            session_id('');
            $this->setRequestGlobals(path: '/articles/example');

            self::assertSame('front', $this->handleGlobals(application: $application));
            self::assertSame(PHP_SESSION_NONE, session_status());
            self::assertSame('', session_id());
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    /**
     * @param class-string $controller
     */
    private function writeApplicationConfig(string $root, string $app, string $routePath, string $controller): void
    {
        mkdir("{$root}/apps/{$app}/config", 0777, true);
        file_put_contents("{$root}/apps/{$app}/config/container.php", <<<'PHP'
<?php
declare(strict_types=1);
return new \Pera\Framework\Container\Container();
PHP);
        file_put_contents("{$root}/apps/{$app}/config/middleware.php", <<<'PHP'
<?php
declare(strict_types=1);
return [];
PHP);
        file_put_contents("{$root}/apps/{$app}/config/routes.php", sprintf(
            "<?php\ndeclare(strict_types=1);\nreturn [[%s, %s, %s]];\n",
            var_export('GET', true),
            var_export($routePath, true),
            '\\' . $controller . '::class',
        ));
    }

    private function session(): Session
    {
        $data = [];

        return new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());
    }

    private function resetNativeSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');
        $_SESSION = [];
    }

    private function setRequestGlobals(string $path): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
    }

    private function handleGlobals(Application $application): string
    {
        ob_start();
        $application->handleGlobals();
        $body = ob_get_clean();
        self::assertIsString($body);

        return $body;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        self::assertIsArray($items);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . '/' . $item;
            if (is_dir($child)) {
                $this->removeDirectory(path: $child);
                continue;
            }

            unlink($child);
        }

        rmdir($path);
    }
}

final readonly class FrontController
{
    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars): Response
    {
        return new Response(body: 'front');
    }
}

final readonly class ApiController
{
    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars): Response
    {
        return new Response(body: 'api');
    }
}

final readonly class StatefulController
{
    public function __construct(private Session $session)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars): Response
    {
        $this->session->set(key: 'visited', value: true);

        return new Response(body: 'stateful');
    }
}
