<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Framework;

use Pera\Framework\Application\IApplication;
use Pera\Framework\Bootstrap\ApplicationBootstrap;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;
use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase
{
    public function testCreatesApplicationFromConfig(): void
    {
        $expectedApplication = new BootstrapApplication();

        $application = ApplicationBootstrap::createApplication(
            config: new ApplicationBootstrapConfig(
                root: '/tmp/example',
                applicationFactory: static fn (ApplicationBootstrapConfig $config): IApplication => $expectedApplication,
            ),
        );

        self::assertSame($expectedApplication, $application);
    }
}

final readonly class BootstrapApplication implements IApplication
{
    public function handleGlobals(): void
    {
    }
}
