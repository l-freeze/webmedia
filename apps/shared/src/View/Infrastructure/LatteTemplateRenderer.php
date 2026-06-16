<?php

declare(strict_types=1);

namespace App\Shared\View\Infrastructure;

use App\Framework\View\TemplateRenderer;
use Latte\Engine;

final class LatteTemplateRenderer implements TemplateRenderer
{
    private const AUTO_REFRESH = true;

    private readonly Engine $engine;

    private readonly string $templateDirectory;

    /** @var array<int, callable(): array<string, mixed>> */
    private array $globalProviders = [];

    public function __construct(string $templateDirectory, string $cacheDirectory)
    {
        $this->engine = new Engine();
        $this->engine->setCacheDirectory($cacheDirectory);
        // Keep template source change detection enabled. If a project can
        // guarantee safe immutable deploys, use a custom renderer explicitly.
        $this->engine->setAutoRefresh(self::AUTO_REFRESH);
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
