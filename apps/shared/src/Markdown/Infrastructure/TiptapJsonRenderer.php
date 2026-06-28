<?php

declare(strict_types=1);

namespace App\Shared\Markdown\Infrastructure;

final readonly class TiptapJsonRenderer
{
    /** @var array<int, int> */
    private const HEADING_LEVELS = [1, 2, 3, 4];

    public function toHtml(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || ($decoded['type'] ?? '') !== 'doc') {
            return '';
        }
        /** @var array<string, mixed> $decoded */

        return $this->renderChildren(node: $decoded);
    }

    public function toPlainText(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || ($decoded['type'] ?? '') !== 'doc') {
            return '';
        }
        /** @var array<string, mixed> $decoded */

        return $this->plainText(node: $decoded);
    }

    /** @param array<string, mixed> $node */
    private function renderNode(array $node): string
    {
        $type = is_string($node['type'] ?? null) ? $node['type'] : '';

        return match ($type) {
            'paragraph' => '<p>' . $this->renderChildren(node: $node) . '</p>',
            'heading' => $this->renderHeading(node: $node),
            'text' => $this->renderText(node: $node),
            'bulletList' => '<ul>' . $this->renderChildren(node: $node) . '</ul>',
            'orderedList' => '<ol>' . $this->renderChildren(node: $node) . '</ol>',
            'listItem' => '<li>' . $this->renderChildren(node: $node) . '</li>',
            'blockquote' => '<blockquote>' . $this->renderChildren(node: $node) . '</blockquote>',
            'codeBlock' => '<pre><code>' . htmlspecialchars($this->plainText(node: $node), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>',
            'hardBreak' => '<br>',
            'horizontalRule' => '<hr>',
            'image' => $this->renderImage(node: $node),
            'table' => '<table>' . $this->renderChildren(node: $node) . '</table>',
            'tableRow' => '<tr>' . $this->renderChildren(node: $node) . '</tr>',
            'tableHeader' => '<th>' . $this->renderChildren(node: $node) . '</th>',
            'tableCell' => '<td>' . $this->renderChildren(node: $node) . '</td>',
            default => $this->renderChildren(node: $node),
        };
    }

    /** @param array<string, mixed> $node */
    private function renderChildren(array $node): string
    {
        $content = $node['content'] ?? [];
        if (!is_array($content)) {
            return '';
        }

        $html = '';
        foreach ($content as $child) {
            if (is_array($child)) {
                /** @var array<string, mixed> $child */
                $html .= $this->renderNode(node: $child);
            }
        }

        return $html;
    }

    /** @param array<string, mixed> $node */
    private function renderHeading(array $node): string
    {
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $level = is_int($attrs['level'] ?? null) ? $attrs['level'] : 2;
        if (!in_array($level, self::HEADING_LEVELS, true)) {
            $level = 2;
        }

        return '<h' . $level . '>' . $this->renderChildren(node: $node) . '</h' . $level . '>';
    }

    /** @param array<string, mixed> $node */
    private function renderText(array $node): string
    {
        $text = is_string($node['text'] ?? null) ? $node['text'] : '';
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $marks = $node['marks'] ?? [];
        if (!is_array($marks)) {
            return $html;
        }

        foreach ($marks as $mark) {
            if (!is_array($mark)) {
                continue;
            }
            /** @var array<string, mixed> $mark */
            $html = $this->applyMark(html: $html, mark: $mark);
        }

        return $html;
    }

    /** @param array<string, mixed> $mark */
    private function applyMark(string $html, array $mark): string
    {
        $type = is_string($mark['type'] ?? null) ? $mark['type'] : '';

        return match ($type) {
            'bold' => '<strong>' . $html . '</strong>',
            'italic' => '<em>' . $html . '</em>',
            'code' => '<code>' . $html . '</code>',
            'strike' => '<s>' . $html . '</s>',
            'underline' => '<u>' . $html . '</u>',
            'link' => $this->renderLink(html: $html, mark: $mark),
            default => $html,
        };
    }

    /** @param array<string, mixed> $mark */
    private function renderLink(string $html, array $mark): string
    {
        $attrs = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];
        $href = is_string($attrs['href'] ?? null) ? $attrs['href'] : '';
        if (!$this->isSafeUrl(url: $href)) {
            return $html;
        }

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . $html . '</a>';
    }

    /** @param array<string, mixed> $node */
    private function renderImage(array $node): string
    {
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $src = is_string($attrs['src'] ?? null) ? $attrs['src'] : '';
        if (!$this->isSafeUrl(url: $src)) {
            return '';
        }
        $alt = is_string($attrs['alt'] ?? null) ? $attrs['alt'] : '';
        $title = is_string($attrs['title'] ?? null) ? $attrs['title'] : '';
        $html = '<img src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        if ($title !== '') {
            $html .= ' title="' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return $html . '>';
    }

    private function isSafeUrl(string $url): bool
    {
        return str_starts_with($url, '/')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, 'http://');
    }

    /** @param array<string, mixed> $node */
    private function plainText(array $node): string
    {
        $text = is_string($node['text'] ?? null) ? $node['text'] : '';
        $content = $node['content'] ?? [];
        if (!is_array($content)) {
            return $text;
        }

        foreach ($content as $child) {
            if (is_array($child)) {
                /** @var array<string, mixed> $child */
                $childText = $this->plainText(node: $child);
                if ($childText !== '') {
                    $text .= ($text === '' ? '' : ' ') . $childText;
                }
            }
        }

        return $text;
    }
}
