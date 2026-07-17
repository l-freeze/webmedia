<?php

declare(strict_types=1);

namespace Tests\Framework;

use Pera\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testRedirectBuildsLocationHeader(): void
    {
        $response = Response::redirect(location: '/admin');

        self::assertSame(302, $response->status);
        self::assertSame('/admin', $response->headers['Location']);
    }
}
