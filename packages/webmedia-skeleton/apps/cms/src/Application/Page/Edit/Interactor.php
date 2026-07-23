<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit;

use App\Cms\Application\Page\Edit\Query\IPageQuery;
use App\Cms\Application\Page\Edit\Query\IImageQuery;

final readonly class Interactor
{
    public function __construct(private IPageQuery $pages, private IImageQuery $images)
    {
    }

    public function execute(Input $input): Output
    {
        $page = $input->id === null ? null : $this->pages->find($input->id);
        $media = $this->images->images();

        return new Output(
            page: $page,
            media: $media,
        );
    }
}
