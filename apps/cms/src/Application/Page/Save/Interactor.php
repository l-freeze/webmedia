<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Domain\Repository\IPageRepository;

final readonly class Interactor
{
    public function __construct(private IPageRepository $pages)
    {
    }

    public function execute(Input $input): Output
    {
        $this->pages->create($input->data);
        return new Output(true);
    }
}
