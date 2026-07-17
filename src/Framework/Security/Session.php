<?php

declare(strict_types=1);

namespace Pera\Framework\Security;

final class Session
{
    public function __construct(
        private ?SessionState $state,
        private NativeSessionRuntime $runtime,
        private readonly bool $secure = false,
    ) {
    }

    /**
     * Create a request-scoped Session that starts the native PHP session only
     * when application code first accesses session state.
     */
    public static function fromNativeGlobals(NativeSessionRuntime $runtime, bool $secure): self
    {
        return new self(state: null, runtime: $runtime, secure: $secure);
    }

    public function regenerate(): void
    {
        $this->state();
        $this->runtime->regenerate();
    }

    public function set(string $key, mixed $value): void
    {
        $this->state()->set(key: $key, value: $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->state()->get(key: $key, default: $default);
    }

    public function has(string $key): bool
    {
        return $this->state()->has(key: $key);
    }

    public function remove(string $key): void
    {
        $this->state()->remove(key: $key);
    }

    public function destroy(): void
    {
        $this->state()->clear();
        $this->runtime->destroy();
    }

    public function flash(string $key, string $value): void
    {
        $this->flashValue(key: $key, value: $value);
    }

    public function flashValue(string $key, mixed $value): void
    {
        $state = $this->state();
        $flash = $state->get(key: '_flash', default: []);
        if (!is_array($flash)) {
            $flash = [];
        }
        $flash[$key] = $value;
        $state->set(key: '_flash', value: $flash);
    }

    public function pullFlash(string $key): ?string
    {
        $value = $this->pullFlashValue(key: $key);
        return is_string($value) ? $value : null;
    }

    public function pullFlashValue(string $key): mixed
    {
        $state = $this->state();
        $flash = $state->get(key: '_flash', default: []);
        if (!is_array($flash)) {
            return null;
        }

        $value = $flash[$key] ?? null;
        unset($flash[$key]);
        $state->set(key: '_flash', value: $flash);
        return $value;
    }

    private function state(): SessionState
    {
        if ($this->state instanceof SessionState) {
            return $this->state;
        }

        $this->runtime->start(secure: $this->secure);

        /** @var array<string, mixed> $_SESSION */
        $sessionData = &$_SESSION;
        $this->state = new SessionState(data: $sessionData);

        return $this->state;
    }
}
