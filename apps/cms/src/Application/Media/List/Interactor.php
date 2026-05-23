<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\List;

use App\Cms\Application\Media\List\Query\IMediaQuery;

final readonly class Interactor
{
    public function __construct(private IMediaQuery $media)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->media->list());
    }
}
