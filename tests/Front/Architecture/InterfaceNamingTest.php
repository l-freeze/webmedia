<?php

declare(strict_types=1);

namespace Tests\Front\Architecture;

use Tests\Support\InterfaceNamingTestCase;

final class InterfaceNamingTest extends InterfaceNamingTestCase
{
    public function testFrontInterfacesUseIPrefix(): void
    {
        $this->assertInterfacesUseIPrefix(dirname(__DIR__, 3) . '/apps/front/src');
    }
}
