<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Detail;

use App\Cms\Application\Inquiry\Detail\Query\IInquiryQuery;

final readonly class Interactor
{
    public function __construct(private IInquiryQuery $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(inquiry: $this->inquiries->find($input->id));
    }
}
