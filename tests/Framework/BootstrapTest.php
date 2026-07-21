<?php

declare(strict_types=1);

namespace Tests\Framework;

use Pera\Framework\Application\IApplication;
use Pera\Framework\Bootstrap\ApplicationBootstrap;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;
use App\Kernel\Application;
use App\Kernel\ApplicationDefinition;
use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase
{
    public function testCreatesApplicationFromConfig(): void
    {
        $root = sys_get_temp_dir() . '/webmedia-bootstrap-test-' . bin2hex(random_bytes(4));
        mkdir($root . '/apps/front/config', 0777, true);
        mkdir($root . '/apps/cms/config', 0777, true);
        $container = <<<'PHP'
<?php
declare(strict_types=1);
return new \Pera\Framework\Container\Container();
PHP;
        $routes = <<<'PHP'
<?php
declare(strict_types=1);
return [];
PHP;
        file_put_contents($root . '/apps/front/config/container.php', $container);
        file_put_contents($root . '/apps/cms/config/container.php', $container);
        file_put_contents($root . '/apps/front/config/routes.php', $routes);
        file_put_contents($root . '/apps/cms/config/routes.php', $routes);

        $application = ApplicationBootstrap::createApplication(
            config: new ApplicationBootstrapConfig(
                root: $root,
                applicationFactory: static fn (ApplicationBootstrapConfig $config): IApplication => new Application(
                    root: $config->root,
                    definitions: [
                        new ApplicationDefinition(
                            name: 'front',
                            pathPrefix: '/',
                            routesPath: '/apps/front/config/routes.php',
                            containerPath: '/apps/front/config/container.php',
                            middlewarePath: '/apps/front/config/middleware.php',
                        ),
                    ],
                ),
            ),
        );

        self::assertInstanceOf(Application::class, $application);
    }
}
