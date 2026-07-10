<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Upload;

use App\Cms\Domain\Entity\MediaUpload;
use App\Cms\Domain\Repository\IMediaRepository;

final readonly class Interactor
{
    public function __construct(private IMediaRepository $media)
    {
    }

    public function execute(Input $input): Output
    {
        $result = $this->media->storeUploadedMedia(new MediaUpload(
            originalName: $this->stringValue(data: $input->file, key: 'name'),
            tmpName: $this->stringValue(data: $input->file, key: 'tmp_name'),
            error: $this->intValue(data: $input->file, key: 'error', default: UPLOAD_ERR_NO_FILE),
            size: $this->intValue(data: $input->file, key: 'size'),
            altText: $input->altText,
            caption: $input->caption,
        ));

        return new Output(uploaded: $result->uploaded, error: $result->error, mediaId: $result->mediaId);
    }

    /** @param array<string, mixed> $data */
    private function stringValue(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) ? (string) $value : '';
    }

    /** @param array<string, mixed> $data */
    private function intValue(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }
}
