<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Application\Media\Delete;
use App\Cms\Application\Media\Upload;

interface IMediaRepository
{
    /** @param array<string, mixed> $file */
    public function storeUploadedMedia(array $file, string $altText, string $caption): Upload\Output;

    public function delete(int $id): Delete\Output;
}
