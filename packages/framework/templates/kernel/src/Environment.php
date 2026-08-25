<?php

declare(strict_types=1);

namespace App\Kernel;

final readonly class Environment
{
    public function string(string $key, string $default): string
    {
        $processValue = getenv($key);
        if ($processValue !== false) {
            return $processValue;
        }

        foreach ([$_ENV, $_SERVER] as $values) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];
            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        return $default;
    }
}
