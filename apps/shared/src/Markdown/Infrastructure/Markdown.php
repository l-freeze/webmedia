<?php

declare(strict_types=1);

namespace App\Shared\Markdown\Infrastructure;

use HTMLPurifier;
use HTMLPurifier_Config;
use League\CommonMark\CommonMarkConverter;

final readonly class Markdown
{
    private CommonMarkConverter $converter;

    private HTMLPurifier $purifier;

    private TiptapJsonRenderer $tiptapJsonRenderer;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,s,u,a[href|title],ul,ol,li,blockquote,pre,code,h1,h2,h3,h4,table,thead,tbody,tr,th,td,img[src|alt|title],hr');
        $config->set('URI.SafeIframeRegexp', null);
        $this->purifier = new HTMLPurifier($config);
        $this->tiptapJsonRenderer = new TiptapJsonRenderer();
    }

    public function toSafeHtml(string $markdown): string
    {
        return $this->purifier->purify((string) $this->converter->convert($markdown));
    }

    public function htmlToSafeHtml(string $html): string
    {
        return $this->purifier->purify($html);
    }

    public function bodyToSafeHtml(string $body, string $format): string
    {
        if ($format === 'rich_text') {
            return $this->htmlToSafeHtml(html: $this->tiptapJsonRenderer->toHtml(json: $body));
        }

        if ($format === 'html') {
            return $this->htmlToSafeHtml(html: $body);
        }

        return $this->toSafeHtml(markdown: $body);
    }
}
