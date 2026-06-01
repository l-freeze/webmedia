<?php

declare(strict_types=1);

namespace Tests\Kernel;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use App\Kernel\Application;
use App\Kernel\ApplicationDefinition;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
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

    /**
     * @param class-string $controller
     */
    private function writeApplicationConfig(string $root, string $app, string $routePath, string $controller): void
    {
        mkdir("{$root}/apps/{$app}/config", 0777, true);
        file_put_contents("{$root}/apps/{$app}/config/container.php", <<<'PHP'
<?php
declare(strict_types=1);
return new \App\Framework\Container\Container();
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
