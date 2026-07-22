<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Framework;

use Pera\Framework\Tests\Support\InterfaceNamingTestCase;

final class InterfaceNamingTest extends InterfaceNamingTestCase
{
    public function testFrameworkInterfacesUseIPrefix(): void
    {
        $this->assertInterfacesUseIPrefix(dirname(__DIR__, 2) . '/src');
    }
}
