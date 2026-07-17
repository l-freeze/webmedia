<?php

declare(strict_types=1);

namespace Pera\Framework\View;

interface ITemplateRenderer
{
    /** @param array<string, mixed> $params */
    public function render(string $template, array $params = []): string;
}
