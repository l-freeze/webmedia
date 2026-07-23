<?php

declare(strict_types=1);

namespace Tests\Cms\Setting;

use App\Cms\Application\Setting\Save\Interactor;
use App\Cms\Domain\Entity\SiteSettings;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Cms\Presentation\Http\Controller\Setting\Update\Controller;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SettingUpdateControllerTest extends TestCase
{
    /** @param array<string, string> $searchInput */
    #[DataProvider('searchInputProvider')]
    public function testSearchSelectionIsConvertedToBoolean(array $searchInput, bool $expected): void
    {
        $repository = new CapturingSettingRepository();
        $sessionData = [];
        $controller = new Controller(
            session: new Session(
                state: new SessionState(data: $sessionData),
                runtime: new NativeSessionRuntime(),
            ),
            save: new Interactor(settings: $repository),
            audit: new AuditReporter(),
        );
        $body = [
            'site_name' => 'Example',
            'site_description' => 'Example site',
            'contact_email' => 'owner@example.com',
            'front_theme' => 'cafe-clean',
            'hero_image' => '',
            'hero_title' => 'Example hero',
            'hero_description' => 'Example hero description',
            ...$searchInput,
        ];

        $response = $controller(
            request: new Request(
                method: 'POST',
                path: '/admin/settings',
                query: [],
                body: $body,
                server: [],
            ),
        );

        self::assertSame($expected, $repository->saved?->searchEnabled->value);
        self::assertSame(Response::HTTP_FOUND, $response->status);
        self::assertSame('/admin/settings', $response->headers['Location'] ?? null);
    }

    /** @return iterable<string, array{array<string, string>, bool}> */
    public static function searchInputProvider(): iterable
    {
        yield 'disabled' => [['search_enabled' => '0'], false];
        yield 'enabled' => [['search_enabled' => '1'], true];
    }

    public function testSettingsTemplateUsesExplicitSearchOptions(): void
    {
        $template = file_get_contents(dirname(__DIR__, 3) . '/apps/cms/templates/setting/edit.latte');
        self::assertIsString($template);

        self::assertStringContainsString('<select name="search_enabled">', $template);
        self::assertStringContainsString('<option value="1"', $template);
        self::assertStringContainsString('<option value="0"', $template);
    }
}

final class CapturingSettingRepository implements ISettingRepository
{
    public ?SiteSettings $saved = null;

    public function save(SiteSettings $settings): void
    {
        $this->saved = $settings;
    }
}
