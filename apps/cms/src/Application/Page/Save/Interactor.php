<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;

final readonly class Interactor
{
    public function __construct(private IPageRepository $pages)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->pages->save(Page::fromInput($input->id, $input->data)));
    }
}
