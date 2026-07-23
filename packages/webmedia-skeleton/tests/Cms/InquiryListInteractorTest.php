<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Application\Inquiry\List\Input;
use App\Cms\Application\Inquiry\List\Interactor;
use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;
use App\Cms\Application\Inquiry\List\Value\Inquiry;
use PHPUnit\Framework\TestCase;

final class InquiryListInteractorTest extends TestCase
{
    public function testPaginatesInquiriesByHundred(): void
    {
        $query = new FixedInquiryQuery(total: 250);

        $output = (new Interactor(inquiries: $query))->execute(input: Input::create(page: 3));

        self::assertSame(3, $output->currentPage);
        self::assertSame(100, $output->perPage);
        self::assertSame(250, $output->totalItems);
        self::assertSame(3, $output->totalPages);
        self::assertSame(200, $query->lastOffset);
        self::assertSame(100, $query->lastLimit);
        self::assertNull($output->nextPage());
        self::assertSame(2, $output->previousPage());
    }
}

final class FixedInquiryQuery implements IInquiryQuery
{
    public int $lastLimit = 0;
    public int $lastOffset = 0;

    public function __construct(private readonly int $total)
    {
    }

    /** @return array<int, Inquiry> */
    public function list(int $limit, int $offset): array
    {
        $this->lastLimit = $limit;
        $this->lastOffset = $offset;

        return [];
    }

    public function count(): int
    {
        return $this->total;
    }
}
