<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Http\ExceptionHandling\IExceptionHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\NativeSessionRuntime;
use App\Kernel\Application;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class ExceptionHandlerTest extends TestCase
{
    public function testApplicationCanConvertUnhandledExceptionToResponse(): void
    {
        $root = sys_get_temp_dir() . '/webmedia-exception-test-' . bin2hex(random_bytes(4));
        mkdir($root . '/apps/front/config', 0777, true);
        mkdir($root . '/apps/cms/config', 0777, true);
        $container = <<<'PHP'
<?php
declare(strict_types=1);
return new \App\Framework\Container\Container();
PHP;
        $routes = <<<'PHP'
<?php
declare(strict_types=1);
return [
    ['GET', '/exception', \Tests\Framework\ExceptionController::class],
];
PHP;
        file_put_contents($root . '/apps/front/config/container.php', $container);
        file_put_contents($root . '/apps/cms/config/container.php', $container);
        file_put_contents($root . '/apps/front/config/routes.php', $routes);
        file_put_contents($root . '/apps/cms/config/routes.php', "<?php\ndeclare(strict_types=1);\nreturn [];\n");

        $application = new Application(
            root: $root,
            sessionRuntime: new NativeSessionRuntime(),
            exceptionHandler: new TestExceptionHandler(),
        );
        $this->setRequestGlobals(path: '/exception');

        ob_start();
        $application->handleGlobals();
        $body = (string) ob_get_clean();

        self::assertSame('handled: controller failed', $body);
    }

    public function testApplicationReturnsBadRequestForMalformedRequestUri(): void
    {
        $root = sys_get_temp_dir() . '/webmedia-bad-request-test-' . bin2hex(random_bytes(4));
        mkdir($root . '/apps/front/config', 0777, true);
        mkdir($root . '/apps/cms/config', 0777, true);
        file_put_contents($root . '/apps/front/config/routes.php', "<?php\ndeclare(strict_types=1);\nreturn [];\n");
        file_put_contents($root . '/apps/cms/config/routes.php', "<?php\ndeclare(strict_types=1);\nreturn [];\n");

        $application = new Application(root: $root);
        $this->setRequestGlobals(path: '%');

        ob_start();
        $application->handleGlobals();
        $body = (string) ob_get_clean();

        self::assertSame('Bad Request', $body);
    }

    private function setRequestGlobals(string $path): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
    }
}

final readonly class ExceptionController
{
    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars): Response
    {
        throw new RuntimeException('controller failed');
    }
}

final readonly class TestExceptionHandler implements IExceptionHandler
{
    public function handle(Throwable $throwable, Request $request): Response
    {
        return new Response(body: 'handled: ' . $throwable->getMessage(), status: 500);
    }
}
