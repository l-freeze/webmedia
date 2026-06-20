<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Infrastructure\Http\Middleware\CmsAuditMiddleware;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;
use PHPUnit\Framework\TestCase;

final class CmsConfigTest extends TestCase
{
    public function testCmsMiddlewareConfigIncludesAuditAfterSessionRequirement(): void
    {
        $middleware = require dirname(__DIR__, 2) . '/apps/cms/config/middleware.php';

        self::assertIsArray($middleware);

        $sessionIndex = array_search(RequireCmsSessionMiddleware::class, $middleware, true);
        $auditIndex = array_search(CmsAuditMiddleware::class, $middleware, true);

        self::assertIsInt($sessionIndex);
        self::assertIsInt($auditIndex);
        self::assertGreaterThan($sessionIndex, $auditIndex);
    }
}
