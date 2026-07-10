<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class SiteSettings
{
    public const DEFAULT_FRONT_THEME = 'cafe-clean';
    public const DEFAULT_HERO_TITLE = '読みやすく、探しやすいWEBメディア';
    public const DEFAULT_HERO_DESCRIPTION = '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。';

    /** @var list<string> */
    public const FRONT_THEMES = ['cafe-clean', 'tech-edge', 'executive-trust', 'dev-docs', 'recipe-fresh', 'pop-girly', 'mama-soft'];

    public function __construct(
        public string $siteName,
        public string $siteDescription,
        public string $contactEmail,
        public string $frontTheme,
        public bool $searchEnabled,
        public string $heroImage,
        public string $heroTitle,
        public string $heroDescription,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromInput(array $data): self
    {
        $frontTheme = self::stringValue(data: $data, key: 'front_theme');
        if (!in_array($frontTheme, self::FRONT_THEMES, true)) {
            $frontTheme = self::DEFAULT_FRONT_THEME;
        }

        return new self(
            siteName: self::stringValue(data: $data, key: 'site_name'),
            siteDescription: self::stringValue(data: $data, key: 'site_description'),
            contactEmail: self::stringValue(data: $data, key: 'contact_email'),
            frontTheme: $frontTheme,
            searchEnabled: self::boolValue(value: $data['search_enabled'] ?? null, default: true),
            heroImage: self::stringValue(data: $data, key: 'hero_image'),
            heroTitle: self::stringValue(data: $data, key: 'hero_title'),
            heroDescription: self::stringValue(data: $data, key: 'hero_description'),
        );
    }

    /** @param array<string, mixed> $data */
    private static function stringValue(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) ? (string) $value : $default;
    }

    private static function boolValue(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 't', 'yes', 'on'], true);
        }

        return $default;
    }
}
