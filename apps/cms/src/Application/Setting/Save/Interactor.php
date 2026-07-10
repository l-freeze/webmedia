<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Save;

use App\Cms\Domain\Entity\SiteSettings;
use App\Cms\Domain\Repository\ISettingRepository;

final readonly class Interactor
{
    public function __construct(private ISettingRepository $settings)
    {
    }

    public function execute(Input $input): Output
    {
        $this->settings->save(new SiteSettings(
            siteName: $this->stringInput(data: $input->data, key: 'site_name'),
            siteDescription: $this->stringInput(data: $input->data, key: 'site_description'),
            contactEmail: $this->stringInput(data: $input->data, key: 'contact_email'),
            frontTheme: $this->frontTheme(data: $input->data),
            searchEnabled: $this->boolInput(value: $input->data['search_enabled'] ?? null, default: true),
            heroImage: $this->stringInput(data: $input->data, key: 'hero_image'),
            heroTitle: $this->stringInput(data: $input->data, key: 'hero_title'),
            heroDescription: $this->stringInput(data: $input->data, key: 'hero_description'),
        ));

        return new Output(saved: true);
    }

    /** @param array<string, mixed> $data */
    private function frontTheme(array $data): string
    {
        $frontTheme = $this->stringInput(data: $data, key: 'front_theme');
        return in_array($frontTheme, SiteSettings::FRONT_THEMES, true) ? $frontTheme : SiteSettings::DEFAULT_FRONT_THEME;
    }

    /** @param array<string, mixed> $data */
    private function stringInput(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) ? (string) $value : $default;
    }

    private function boolInput(mixed $value, bool $default): bool
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
