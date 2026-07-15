<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Application\Page\Restore\Input;
use App\Cms\Application\Page\Restore\Interactor;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\PageId;
use App\Cms\Domain\Value\PageSlug;
use App\Cms\Domain\Value\PageVersionNo;
use PHPUnit\Framework\TestCase;

final class PageRestoreInteractorTest extends TestCase
{
    public function testRestoresSelectedPageVersion(): void
    {
        $repository = new RecordingPageRepository();

        $output = (new Interactor(pages: $repository))->execute(input: Input::create(pageId: 7, versionNo: 3));

        self::assertTrue($output->restored);
        self::assertSame(7, $repository->restoredPageId);
        self::assertSame(3, $repository->restoredVersionNo);
    }
}

final class RecordingPageRepository implements IPageRepository
{
    public ?int $restoredPageId = null;
    public ?int $restoredVersionNo = null;

    public function save(Page $page): PageId
    {
        return new PageId(value: 1);
    }

    public function slugExists(PageSlug $slug, ?PageId $exceptId): bool
    {
        return false;
    }

    public function restore(PageId $pageId, PageVersionNo $versionNo): void
    {
        $this->restoredPageId = $pageId->value;
        $this->restoredVersionNo = $versionNo->value;
    }
}
