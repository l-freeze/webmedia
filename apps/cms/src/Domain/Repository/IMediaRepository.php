<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Value\MediaDeleteResult;
use App\Cms\Domain\Value\MediaUploadResult;

interface IMediaRepository
{
    /** @param array<string, mixed> $file */
    public function storeUploadedMedia(array $file, string $altText, string $caption): MediaUploadResult;

    public function delete(int $id): MediaDeleteResult;
}
