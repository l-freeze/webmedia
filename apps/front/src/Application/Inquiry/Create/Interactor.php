<?php

declare(strict_types=1);

namespace App\Front\Application\Inquiry\Create;

use App\Front\Domain\Repository\IInquiryRepository;

final readonly class Interactor
{
    public function __construct(private IInquiryRepository $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        $this->inquiries->create($input->name, $input->email, $input->subject, $input->message);
        return new Output(true);
    }
}
