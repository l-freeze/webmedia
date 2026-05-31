<?php

declare(strict_types=1);

namespace App\Framework\Security;

final class Session
{
    public function __construct(private SessionState $state, private NativeSessionRuntime $runtime)
    {
    }

    public function regenerate(): void
    {
        $this->runtime->regenerate();
    }

    public function set(string $key, mixed $value): void
    {
        $this->state->set(key: $key, value: $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->state->get(key: $key, default: $default);
    }

    public function has(string $key): bool
    {
        return $this->state->has(key: $key);
    }

    public function remove(string $key): void
    {
        $this->state->remove(key: $key);
    }

    public function destroy(): void
    {
        $this->state->clear();
        $this->runtime->destroy();
    }

    public function flash(string $key, string $value): void
    {
        $this->flashValue(key: $key, value: $value);
    }

    public function flashValue(string $key, mixed $value): void
    {
        $flash = $this->state->get(key: '_flash', default: []);
        if (!is_array($flash)) {
            $flash = [];
        }
        $flash[$key] = $value;
        $this->state->set(key: '_flash', value: $flash);
    }

    public function pullFlash(string $key): ?string
    {
        $value = $this->pullFlashValue(key: $key);
        return is_string($value) ? $value : null;
    }

    public function pullFlashValue(string $key): mixed
    {
        $flash = $this->state->get(key: '_flash', default: []);
        if (!is_array($flash)) {
            return null;
        }

        $value = $flash[$key] ?? null;
        unset($flash[$key]);
        $this->state->set(key: '_flash', value: $flash);
        return $value;
    }
}
