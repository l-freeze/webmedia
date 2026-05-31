<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Delete;

use App\Cms\Domain\Repository\IMediaRepository;

final readonly class Interactor
{
    public function __construct(private IMediaRepository $media)
    {
    }

    public function execute(Input $input): Output
    {
        return $this->media->delete(id: $input->id);
    }
}
