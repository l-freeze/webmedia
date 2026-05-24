<?php

declare(strict_types=1);

namespace App\Framework\View;

use Latte\Engine;

final class Renderer
{
    private Engine $engine;

    private string $templateDirectory;

    /** @var array<int, callable(): array<string, mixed>> */
    private array $globalProviders = [];

    public function __construct(string $templateDirectory, string $cacheDirectory)
    {
        $this->engine = new Engine();
        $this->engine->setTempDirectory($cacheDirectory);
        $this->templateDirectory = rtrim($templateDirectory, '/');
    }

    /** @param callable(): array<string, mixed> $provider */
    public function addGlobalProvider(callable $provider): void
    {
        $this->globalProviders[] = $provider;
    }

    /** @param array<string, mixed> $params */
    public function render(string $template, array $params = []): string
    {
        $globalParams = [];
        foreach ($this->globalProviders as $provider) {
            $globalParams = array_merge($globalParams, $provider());
        }

        return $this->engine->renderToString(
            $this->templateDirectory . '/' . ltrim($template, '/'),
            array_merge($globalParams, $params),
        );
    }
}
