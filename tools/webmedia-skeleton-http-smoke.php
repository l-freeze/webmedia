<?php

declare(strict_types=1);

$marker = $argv[1] ?? '';
$verifyOnly = ($argv[2] ?? '') === '--verify-only';
if ($marker === '') {
    throw new RuntimeException('A site-name marker is required.');
}

$cookies = [];

/**
 * @param array<string, scalar|null> $body
 * @param array<string, string> $cookies
 * @return array{status:int, headers:array<string, list<string>>, body:string}
 */
function request(string $method, string $path, array $body = [], array &$cookies = []): array
{
    $headers = ['Accept: text/html'];
    if ($cookies !== []) {
        $pairs = [];
        foreach ($cookies as $name => $value) {
            $pairs[] = $name . '=' . $value;
        }
        $headers[] = 'Cookie: ' . implode('; ', $pairs);
    }
    $options = [
        'method' => $method,
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 10,
        'header' => implode("\r\n", $headers),
    ];
    if ($method === 'POST') {
        $options['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
        $options['content'] = http_build_query($body);
    }
    $context = stream_context_create(['http' => $options]);
    $responseBody = file_get_contents('http://127.0.0.1' . $path, false, $context);
    if (!is_string($responseBody)) {
        throw new RuntimeException("HTTP request failed: {$method} {$path}");
    }

    $responseHeaders = http_get_last_response_headers();
    if (!is_array($responseHeaders)) {
        throw new RuntimeException("HTTP response headers were not available: {$method} {$path}");
    }
    $status = 0;
    $parsedHeaders = [];
    foreach ($responseHeaders as $index => $header) {
        if ($index === 0 && preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match) === 1) {
            $status = (int) $match[1];
            continue;
        }
        $separator = strpos($header, ':');
        if ($separator === false) {
            continue;
        }
        $name = strtolower(trim(substr($header, 0, $separator)));
        $value = trim(substr($header, $separator + 1));
        $parsedHeaders[$name][] = $value;
        if ($name === 'set-cookie' && preg_match('/^([^=;\s]+)=([^;]*)/', $value, $match) === 1) {
            $cookies[$match[1]] = $match[2];
        }
    }

    return ['status' => $status, 'headers' => $parsedHeaders, 'body' => $responseBody];
}

function csrfToken(string $html): string
{
    if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $match) !== 1) {
        throw new RuntimeException('CSRF token was not rendered.');
    }

    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
}

/** @param array{status:int, headers:array<string, list<string>>, body:string} $response */
function assertStatus(array $response, int $expected, string $operation): void
{
    if ($response['status'] !== $expected) {
        throw new RuntimeException("{$operation} returned HTTP {$response['status']}");
    }
}

$publicCookies = [];
$front = request(method: 'GET', path: '/', cookies: $publicCookies);
assertStatus(response: $front, expected: 200, operation: 'Front page');
if ($publicCookies !== [] || isset($front['headers']['set-cookie'])) {
    throw new RuntimeException('Stateless Front page unexpectedly started a Session.');
}
if (($front['headers']['x-content-type-options'][0] ?? null) !== 'nosniff') {
    throw new RuntimeException('Front security headers are missing.');
}

if ($verifyOnly) {
    if (!str_contains($front['body'], $marker)) {
        throw new RuntimeException('Saved site settings did not survive the worker restart.');
    }
    echo "Verified persisted Front state after worker restart.\n";
    exit(0);
}

$loginForm = request(method: 'GET', path: '/admin/login', cookies: $cookies);
assertStatus(response: $loginForm, expected: 200, operation: 'CMS login form');
$login = request(method: 'POST', path: '/admin/login', body: [
    '_csrf' => csrfToken(html: $loginForm['body']),
    'email' => 'admin@example.com',
    'password' => 'password',
], cookies: $cookies);
assertStatus(response: $login, expected: 302, operation: 'CMS login');
if (($login['headers']['location'][0] ?? null) !== '/admin') {
    throw new RuntimeException('CMS login did not redirect to the dashboard.');
}

$settingsForm = request(method: 'GET', path: '/admin/settings', cookies: $cookies);
assertStatus(response: $settingsForm, expected: 200, operation: 'CMS settings form');
$save = request(method: 'POST', path: '/admin/settings', body: [
    '_csrf' => csrfToken(html: $settingsForm['body']),
    'site_name' => $marker,
    'site_description' => 'Isolated Webmedia Skeleton acceptance test',
    'contact_email' => 'acceptance@example.test',
    'front_theme' => 'cafe-clean',
    'search_enabled' => '1',
    'hero_image' => '',
    'hero_title' => 'Verified Skeleton',
    'hero_description' => 'Generated, installed, migrated, and started in isolation.',
], cookies: $cookies);
assertStatus(response: $save, expected: 302, operation: 'CMS settings update');
if (($save['headers']['location'][0] ?? null) !== '/admin/settings') {
    throw new RuntimeException('CMS settings update did not redirect to its edit page.');
}

$publicCookies = [];
$updatedFront = request(method: 'GET', path: '/', cookies: $publicCookies);
assertStatus(response: $updatedFront, expected: 200, operation: 'Updated Front page');
if (!str_contains($updatedFront['body'], $marker)) {
    throw new RuntimeException('CMS settings update was not reflected by the Front application.');
}
if ($publicCookies !== [] || isset($updatedFront['headers']['set-cookie'])) {
    throw new RuntimeException('Updated Front page unexpectedly started a Session.');
}

echo "Verified Front, CMS login, CSRF, Session, persistence, and cross-app rendering.\n";
