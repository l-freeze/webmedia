<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Application\IApplication;
use App\Framework\Bootstrap\ApplicationBootstrap;
use App\Framework\Bootstrap\ApplicationBootstrapConfig;
use App\Kernel\Application;
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
return new \App\Framework\Container\Container();
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
                applicationFactory: static fn (ApplicationBootstrapConfig $config): IApplication => new Application(root: $config->root),
            ),
        );

        self::assertInstanceOf(Application::class, $application);
    }
}
