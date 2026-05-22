<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testInputReadsBodyBeforeQueryAndTrimsScalarValues(): void
    {
        $request = new Request('POST', '/contact', ['name' => ' query '], ['name' => ' body '], []);

        self::assertSame('body', $request->input('name'));
    }

    public function testInputReturnsDefaultForMissingValue(): void
    {
        $request = new Request('GET', '/', [], [], []);

        self::assertSame('fallback', $request->input('missing', 'fallback'));
    }
}
