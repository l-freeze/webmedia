<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testInputReadsBodyBeforeQueryWithoutNormalizingValue(): void
    {
        $request = new Request(method: 'POST', path: '/contact', query: ['name' => ' query '], body: ['name' => ' body '], server: []);

        self::assertSame(' body ', $request->input(key: 'name'));
    }

    public function testInputReturnsDefaultForMissingValue(): void
    {
        $request = new Request(method: 'GET', path: '/', query: [], body: [], server: []);

        self::assertSame('fallback', $request->input(key: 'missing', default: 'fallback'));
    }

    public function testFromGlobalsBuildsRequestFromSuperglobals(): void
    {
        $previousServer = $_SERVER;
        $previousGet = $_GET;
        $previousPost = $_POST;
        $previousFiles = $_FILES;

        try {
            $_SERVER = [
                'REQUEST_METHOD' => 'post',
                'REQUEST_URI' => '/admin/articles?status=draft',
                'HTTPS' => 'on',
            ];
            $_GET = ['status' => 'draft'];
            $_POST = ['title' => 'Example'];
            $_FILES = [];

            $request = Request::fromGlobals();

            self::assertSame('POST', $request->method);
            self::assertSame('/admin/articles', $request->path);
            self::assertSame('draft', $request->query['status']);
            self::assertSame('Example', $request->body['title']);
            self::assertTrue($request->isHttps());
        } finally {
            $_SERVER = $previousServer;
            $_GET = $previousGet;
            $_POST = $previousPost;
            $_FILES = $previousFiles;
        }
    }

    public function testDetectsHttpsFromForwardedProto(): void
    {
        $request = new Request(
            method: 'GET',
            path: '/',
            query: [],
            body: [],
            server: ['HTTP_X_FORWARDED_PROTO' => 'https'],
        );

        self::assertTrue($request->isHttps());
    }
}
