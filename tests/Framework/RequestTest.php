<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testInputReadsBodyBeforeQueryAndTrimsScalarValues(): void
    {
        $request = new Request(method: 'POST', path: '/contact', query: ['name' => ' query '], body: ['name' => ' body '], server: []);

        self::assertSame('body', $request->input(key: 'name'));
    }

    public function testInputReturnsDefaultForMissingValue(): void
    {
        $request = new Request(method: 'GET', path: '/', query: [], body: [], server: []);

        self::assertSame('fallback', $request->input(key: 'missing', default: 'fallback'));
    }
}
