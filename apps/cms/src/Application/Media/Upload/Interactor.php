<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Upload;

use App\Cms\Domain\Repository\IMediaRepository;

final readonly class Interactor
{
    public function __construct(private IMediaRepository $media)
    {
    }

    public function execute(Input $input): Output
    {
        return $this->media->storeUploadedMedia(file: $input->file, altText: $input->altText, caption: $input->caption);
    }
}
