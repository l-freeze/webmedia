<?php

declare(strict_types=1);

namespace App\Framework\View;

use Latte\Engine;

final class Renderer
{
    private Engine $engine;

    private string $templateDirectory;

    public function __construct(string $templateDirectory, string $cacheDirectory)
    {
        $this->engine = new Engine();
        $this->engine->setTempDirectory($cacheDirectory);
        $this->templateDirectory = rtrim($templateDirectory, '/');
    }

    /** @param array<string, mixed> $params */
    public function render(string $template, array $params = []): string
    {
        return $this->engine->renderToString($this->templateDirectory . '/' . ltrim($template, '/'), $params);
    }
}
