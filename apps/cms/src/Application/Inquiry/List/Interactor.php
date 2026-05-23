<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List;

use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;

final readonly class Interactor
{
    public function __construct(private IInquiryQuery $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->inquiries->list());
    }
}
