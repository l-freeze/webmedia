<?php

declare(strict_types=1);

namespace Tests\Shared\Architecture;

use Tests\Support\InterfaceNamingTestCase;

final class InterfaceNamingTest extends InterfaceNamingTestCase
{
    public function testSharedInterfacesUseIPrefix(): void
    {
        $this->assertInterfacesUseIPrefix(dirname(__DIR__, 3) . '/apps/shared/src');
    }
}
