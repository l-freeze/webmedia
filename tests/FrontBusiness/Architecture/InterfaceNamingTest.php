<?php

declare(strict_types=1);

namespace Tests\FrontBusiness\Architecture;

use Tests\Support\InterfaceNamingTestCase;

final class InterfaceNamingTest extends InterfaceNamingTestCase
{
    public function testFrontBusinessInterfacesUseIPrefix(): void
    {
        $this->assertInterfacesUseIPrefix(dirname(__DIR__, 3) . '/apps/front-business/src');
    }
}
