<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\MediaUpload;
use App\Cms\Domain\Value\MediaDeleteResult;
use App\Cms\Domain\Value\MediaUploadResult;

interface IMediaRepository
{
    public function storeUploadedMedia(MediaUpload $upload): MediaUploadResult;

    public function delete(int $id): MediaDeleteResult;
}
