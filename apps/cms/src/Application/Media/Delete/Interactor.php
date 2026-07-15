<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Delete;

use App\Cms\Domain\Repository\IMediaRepository;
use App\Cms\Domain\Value\MediaId;

final readonly class Interactor
{
    public function __construct(private IMediaRepository $media)
    {
    }

    public function execute(Input $input): Output
    {
        $mediaId = new MediaId(value: $input->id);
        $result = $this->media->delete($mediaId);

        return new Output(deleted: $result->deleted, error: $result->error);
    }
}
