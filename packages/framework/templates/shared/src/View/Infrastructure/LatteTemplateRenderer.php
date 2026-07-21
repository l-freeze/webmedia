<?php

declare(strict_types=1);

namespace App\Shared\View\Infrastructure;

use Pera\Framework\View\ITemplateRenderer;
use Latte\Engine;

final class LatteTemplateRenderer implements ITemplateRenderer
{
    private const AUTO_REFRESH = true;

    private Engine $engine;

    private string $templateDirectory;

    /** @var array<int, callable(): array<string, mixed>> */
    private array $globalProviders = [];

    public function __construct(string $templateDirectory, ?string $cacheDirectory = null)
    {
        $this->engine = new Engine();
        if ($cacheDirectory !== null) {
            // 日本語: compiled view cache は worker mode では基本不要です。
            // 有効にするのは、template class が request をまたいで残らない
            // classic/non-worker runtime を使う場合に限定してください。
            // English: compiled view cache is usually unnecessary in worker mode.
            // Enable it only for classic/non-worker runtimes where template
            // classes do not stay loaded across requests.
            $this->engine->setCacheDirectory($cacheDirectory);
            $this->engine->setAutoRefresh(self::AUTO_REFRESH);
        }
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
