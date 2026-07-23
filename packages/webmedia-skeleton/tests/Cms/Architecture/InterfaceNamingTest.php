<?php

declare(strict_types=1);

namespace Tests\Cms\Architecture;

use Tests\Support\InterfaceNamingTestCase;

final class InterfaceNamingTest extends InterfaceNamingTestCase
{
    public function testCmsInterfacesUseIPrefix(): void
    {
        $this->assertInterfacesUseIPrefix(dirname(__DIR__, 3) . '/apps/cms/src');
    }
}
