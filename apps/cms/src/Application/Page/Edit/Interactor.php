<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit;

use App\Cms\Application\Page\Edit\Query\IPageQuery;
use App\Cms\Application\Media\ImagePicker\Query\IImageQuery;

final readonly class Interactor
{
    public function __construct(private IPageQuery $pages, private IImageQuery $images)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(
            page: $input->id === null ? null : $this->pages->find(id: $input->id),
            media: $this->images->images(),
        );
    }
}
