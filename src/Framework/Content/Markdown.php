<?php

declare(strict_types=1);

namespace App\Framework\Content;

use HTMLPurifier;
use HTMLPurifier_Config;
use League\CommonMark\CommonMarkConverter;

final class Markdown
{
    private CommonMarkConverter $converter;

    private HTMLPurifier $purifier;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,a[href|title],ul,ol,li,blockquote,pre,code,h2,h3,h4,table,thead,tbody,tr,th,td,img[src|alt|title]');
        $config->set('URI.SafeIframeRegexp', null);
        $this->purifier = new HTMLPurifier($config);
    }

    public function toSafeHtml(string $markdown): string
    {
        return $this->purifier->purify((string) $this->converter->convert($markdown));
    }
}
