<?php

declare(strict_types=1);

namespace Tests\Framework;

use Pera\Framework\Http\Exception\MalformedRequestException;
use Pera\Framework\Http\Request;
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

    public function testBodyInputReadsOnlyBody(): void
    {
        $request = new Request(method: 'POST', path: '/contact', query: ['_csrf' => 'query-token'], body: ['_csrf' => 'body-token'], server: []);

        self::assertSame('body-token', $request->bodyInput(key: '_csrf'));
    }

    public function testBodyInputDoesNotFallbackToQuery(): void
    {
        $request = new Request(method: 'POST', path: '/contact', query: ['_csrf' => 'query-token'], body: [], server: []);

        self::assertSame('', $request->bodyInput(key: '_csrf'));
    }

    public function testQueryInputReadsOnlyQuery(): void
    {
        $request = new Request(method: 'GET', path: '/search', query: ['q' => 'php'], body: ['q' => 'body'], server: []);

        self::assertSame('php', $request->queryInput(key: 'q'));
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

    public function testFromGlobalsRejectsMalformedRequestUri(): void
    {
        $previousServer = $_SERVER;
        $previousGet = $_GET;
        $previousPost = $_POST;
        $previousFiles = $_FILES;

        try {
            $_SERVER = [
                'REQUEST_METHOD' => 'get',
                'REQUEST_URI' => '%',
            ];
            $_GET = [];
            $_POST = [];
            $_FILES = [];

            $this->expectException(MalformedRequestException::class);
            $this->expectExceptionMessage('Request URI is malformed.');

            Request::fromGlobals();
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

    public function testReadsAttributesAndCreatesCopyWithAttribute(): void
    {
        $request = new Request(method: 'GET', path: '/', query: [], body: [], server: []);
        $updated = $request->withAttribute(key: 'route.metadata', value: ['feature' => 'enabled']);

        self::assertNull($request->attribute(key: 'route.metadata'));
        self::assertSame(['feature' => 'enabled'], $updated->attribute(key: 'route.metadata'));
        self::assertSame('fallback', $updated->attribute(key: 'missing', default: 'fallback'));
    }
}
