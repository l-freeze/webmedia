<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Application\Media\Delete\Output as DeleteOutput;
use App\Cms\Application\Media\Upload\Output;

interface IMediaRepository
{
    /** @param array<string, mixed> $file */
    public function storeUploadedMedia(array $file, string $altText, string $caption): Output;

    public function delete(int $id): DeleteOutput;
}
