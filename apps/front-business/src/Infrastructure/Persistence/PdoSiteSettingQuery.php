<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Application\Layout\Load\Query\ISiteSettingQuery;
use App\FrontBusiness\Application\Layout\Load\Value\SiteSettings;

final readonly class PdoSiteSettingQuery implements ISiteSettingQuery
{
    private const DEFAULT_SITE_NAME = '業務システム設計室';
    private const DEFAULT_SITE_DESCRIPTION = '管理画面、権限、監査ログ、履歴、状態変更を整理し、後から説明できる業務システムを設計します。';
    private const DEFAULT_THEME = 'business-architecture';
    private const DEFAULT_HERO_TITLE = '業務システム・管理画面の権限、監査、履歴を設計します';
    private const DEFAULT_HERO_DESCRIPTION = '安く作ることより、後で壊れない責任境界を先に決める。初期リスクレビューと設計・実装で、説明できる管理画面を作ります。';

    public function current(): SiteSettings
    {
        return new SiteSettings(
            siteName: self::DEFAULT_SITE_NAME,
            siteDescription: self::DEFAULT_SITE_DESCRIPTION,
            frontTheme: self::DEFAULT_THEME,
            searchEnabled: false,
            heroImage: '',
            heroTitle: self::DEFAULT_HERO_TITLE,
            heroDescription: self::DEFAULT_HERO_DESCRIPTION,
        );
    }
}
