<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Value\MediaDeleteResult;
use App\Cms\Domain\Value\MediaUploadResult;

interface IMediaRepository
{
    /** @param array{name?:mixed,type?:mixed,tmp_name?:mixed,error?:mixed,size?:mixed} $file */
    public function storeUploadedMedia(array $file, string $altText, string $caption): MediaUploadResult;

    public function delete(int $id): MediaDeleteResult;
}
