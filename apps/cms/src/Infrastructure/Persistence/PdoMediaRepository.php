<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\IMediaRepository;
use App\Cms\Domain\Value\MediaDeleteResult;
use App\Cms\Domain\Value\MediaUploadResult;
use App\Cms\Infrastructure\Media\VipsImageVariantGenerator;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoMediaRepository implements IMediaRepository
{
    private const BYTES_PER_MEGABYTE = 1048576;
    private const IMAGE_MAX_MEGABYTES = 20;
    private const VIDEO_MAX_MEGABYTES = 512;
    private const MAX_IMAGE_SIZE = self::IMAGE_MAX_MEGABYTES * self::BYTES_PER_MEGABYTE;
    private const MAX_VIDEO_SIZE = self::VIDEO_MAX_MEGABYTES * self::BYTES_PER_MEGABYTE;
    private const UPLOAD_DIRECTORY_PERMISSIONS = 0775;
    private const STORED_FILE_RANDOM_BYTES = 8;
    private const PROJECT_ROOT_PARENT_DEPTH = 5;
    private const ORIGINAL_FILE_NAME_MAX_LENGTH = 190;

    /** @var array<string, string> */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
        'video/avi' => 'avi',
        'application/x-troff-msvideo' => 'avi',
    ];

    public function __construct(private PDO $pdo, private VipsImageVariantGenerator $imageVariants)
    {
    }

    /** @param array<string, mixed> $file */
    public function storeUploadedMedia(array $file, string $altText, string $caption): MediaUploadResult
    {
        $input = PdoRow::from(values: $file);
        if ($input->int(key: 'error', default: UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new MediaUploadResult(uploaded: false, error: $this->uploadErrorMessage(error: $input->int(key: 'error', default: UPLOAD_ERR_NO_FILE)));
        }

        $tmpName = $input->string(key: 'tmp_name');
        if ($tmpName === '' || !is_file($tmpName)) {
            return new MediaUploadResult(uploaded: false, error: 'アップロードされたファイルを確認できません。');
        }

        $mimeType = $this->detectMimeType(tmpName: $tmpName);
        if (!isset(self::EXTENSIONS[$mimeType])) {
            return new MediaUploadResult(uploaded: false, error: 'アップロードできるメディアは JPEG / PNG / WebP / GIF / MP4 / WebM / Ogg / MOV / AVI です。');
        }

        $fileSize = $input->int(key: 'size');
        if ($fileSize <= 0 || $fileSize > $this->maxFileSize(mimeType: $mimeType)) {
            return new MediaUploadResult(uploaded: false, error: $this->fileSizeMessage(mimeType: $mimeType));
        }

        [$width, $height] = $this->imageDimensions(tmpName: $tmpName, mimeType: $mimeType);

        $publicDir = $this->publicUploadDir();
        if (!is_dir($publicDir)) {
            mkdir($publicDir, self::UPLOAD_DIRECTORY_PERMISSIONS, true);
        }

        $extension = self::EXTENSIONS[$mimeType];
        $storedFileName = date('YmdHis') . '-' . bin2hex(random_bytes(self::STORED_FILE_RANDOM_BYTES)) . '.' . $extension;
        $destination = $publicDir . '/' . $storedFileName;

        if (!$this->moveUploadedFile(tmpName: $tmpName, destination: $destination)) {
            return new MediaUploadResult(uploaded: false, error: 'メディアファイルの保存に失敗しました。');
        }

        $path = '/uploads/media/' . $storedFileName;
        $variants = $this->imageVariants->generate(
            absolutePath: $destination,
            publicPath: $path,
            mimeType: $mimeType,
            width: $width,
            height: $height,
        );

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO media_files (file_name, path, mime_type, file_size, width, height, thumbnail_path, optimized_path, alt_text, caption, created_at) VALUES (:file_name, :path, :mime_type, :file_size, :width, :height, :thumbnail_path, :optimized_path, :alt_text, :caption, :created_at) RETURNING id');
        PdoHelper::execute(statement: $stmt, params: [
            'file_name' => $this->safeOriginalName(fileName: $input->string(key: 'name', default: $storedFileName)),
            'path' => $path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'thumbnail_path' => $variants->thumbnailPath,
            'optimized_path' => $variants->optimizedPath,
            'alt_text' => $altText === '' ? null : $altText,
            'caption' => $caption === '' ? null : $caption,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $mediaId = PdoHelper::fetch(statement: $stmt);

        return new MediaUploadResult(uploaded: true, mediaId: $mediaId === null ? null : PdoRow::from(values: $mediaId)->int(key: 'id'));
    }

    public function delete(int $id): MediaDeleteResult
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT path, thumbnail_path, optimized_path FROM media_files WHERE id = :id LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return new MediaDeleteResult(deleted: false, error: '削除対象のメディアが見つかりません。');
        }
        $media = PdoRow::from(values: $row);
        $path = $media->string(key: 'path');

        $this->pdo->beginTransaction();
        try {
            $this->clearEyecatchReferences(path: $path);
            $delete = PdoHelper::prepare(pdo: $this->pdo, sql: 'DELETE FROM media_files WHERE id = :id');
            PdoHelper::execute(statement: $delete, params: ['id' => $id]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        foreach ([$path, $media->nullableString(key: 'thumbnail_path'), $media->nullableString(key: 'optimized_path')] as $publicPath) {
            if ($publicPath === null) {
                continue;
            }
            $filePath = $this->publicPath(path: $publicPath);
            if ($filePath !== null && is_file($filePath)) {
                unlink($filePath);
            }
        }

        return new MediaDeleteResult(deleted: true);
    }

    private function detectMimeType(string $tmpName): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($tmpName);
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'メディアサイズが上限を超えています。画像は20MB以内、動画は512MB以内にしてください。',
            UPLOAD_ERR_PARTIAL => 'メディアファイルのアップロードが途中で失敗しました。もう一度お試しください。',
            UPLOAD_ERR_NO_FILE => 'メディアファイルを選択してください。',
            UPLOAD_ERR_NO_TMP_DIR => 'アップロード用の一時ディレクトリが見つかりません。',
            UPLOAD_ERR_CANT_WRITE => 'メディアファイルをサーバーに保存できませんでした。',
            UPLOAD_ERR_EXTENSION => 'サーバー設定によりメディアアップロードが停止されました。',
            default => 'メディアアップロードに失敗しました。',
        };
    }

    private function maxFileSize(string $mimeType): int
    {
        return $this->isVideoMime(mimeType: $mimeType) ? self::MAX_VIDEO_SIZE : self::MAX_IMAGE_SIZE;
    }

    private function fileSizeMessage(string $mimeType): string
    {
        return $this->isVideoMime(mimeType: $mimeType)
            ? '動画サイズは512MB以内にしてください。'
            : '画像サイズは20MB以内にしてください。';
    }

    private function isVideoMime(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/')
            || $mimeType === 'application/x-troff-msvideo';
    }

    /** @return array{0: ?int, 1: ?int} */
    private function imageDimensions(string $tmpName, string $mimeType): array
    {
        if (!str_starts_with($mimeType, 'image/')) {
            return [null, null];
        }

        $size = @getimagesize($tmpName);
        if (!is_array($size)) {
            return [null, null];
        }

        $width = $size[0] > 0 ? $size[0] : null;
        $height = $size[1] > 0 ? $size[1] : null;

        return [$width, $height];
    }

    private function publicUploadDir(): string
    {
        return dirname(__DIR__, self::PROJECT_ROOT_PARENT_DEPTH) . '/public/uploads/media';
    }

    private function moveUploadedFile(string $tmpName, string $destination): bool
    {
        if (is_uploaded_file($tmpName)) {
            return move_uploaded_file($tmpName, $destination);
        }

        return rename($tmpName, $destination);
    }

    private function safeOriginalName(string $fileName): string
    {
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^\w.\-]+/u', '-', $fileName) ?: 'upload';

        return mb_substr($fileName, 0, self::ORIGINAL_FILE_NAME_MAX_LENGTH);
    }

    private function clearEyecatchReferences(string $path): void
    {
        $articles = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE articles SET eyecatch_path = NULL WHERE eyecatch_path = :path');
        PdoHelper::execute(statement: $articles, params: ['path' => $path]);

        $versions = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE article_versions SET eyecatch_path = NULL WHERE eyecatch_path = :path');
        PdoHelper::execute(statement: $versions, params: ['path' => $path]);
    }

    private function publicPath(string $path): ?string
    {
        if (!str_starts_with($path, '/uploads/media/')) {
            return null;
        }

        return $this->publicUploadDir() . '/' . basename($path);
    }
}
