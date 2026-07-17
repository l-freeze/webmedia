<?php

declare(strict_types=1);

namespace Pera\Framework\Runtime;

final readonly class RequestCookieMap
{
    private function __construct()
    {
    }

    /**
     * @param array<array-key, mixed> $cookies
     * @return array<string, string>
     */
    public static function fromMixed(array $cookies): array
    {
        $mapped = [];
        foreach ($cookies as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $mapped[$key] = (string) $value;
            }
        }

        return $mapped;
    }
}
