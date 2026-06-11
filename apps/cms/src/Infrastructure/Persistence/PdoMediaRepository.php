<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Media\Delete;
use App\Cms\Application\Media\Upload;
use App\Cms\Domain\Repository\IMediaRepository;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;
use Throwable;

final readonly class PdoMediaRepository implements IMediaRepository
{
    private const MAX_IMAGE_SIZE = 20971520;
    private const MAX_VIDEO_SIZE = 536870912;

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

    public function __construct(private PDO $pdo)
    {
    }

    /** @param array<string, mixed> $file */
    public function storeUploadedMedia(array $file, string $altText, string $caption): Upload\Output
    {
        $input = PdoRow::from(values: $file);
        if ($input->int(key: 'error', default: UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new Upload\Output(uploaded: false, error: $this->uploadErrorMessage(error: $input->int(key: 'error', default: UPLOAD_ERR_NO_FILE)));
        }

        $tmpName = $input->string(key: 'tmp_name');
        if ($tmpName === '' || !is_file($tmpName)) {
            return new Upload\Output(uploaded: false, error: 'アップロードされたファイルを確認できません。');
        }

        $mimeType = $this->detectMimeType(tmpName: $tmpName);
        if (!isset(self::EXTENSIONS[$mimeType])) {
            return new Upload\Output(uploaded: false, error: 'アップロードできるメディアは JPEG / PNG / WebP / GIF / MP4 / WebM / Ogg / MOV / AVI です。');
        }

        $fileSize = $input->int(key: 'size');
        if ($fileSize <= 0 || $fileSize > $this->maxFileSize(mimeType: $mimeType)) {
            return new Upload\Output(uploaded: false, error: $this->fileSizeMessage(mimeType: $mimeType));
        }

        $publicDir = $this->publicUploadDir();
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0775, true);
        }

        $extension = self::EXTENSIONS[$mimeType];
        $storedFileName = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $publicDir . '/' . $storedFileName;

        if (!$this->moveUploadedFile(tmpName: $tmpName, destination: $destination)) {
            return new Upload\Output(uploaded: false, error: 'メディアファイルの保存に失敗しました。');
        }

        $path = '/uploads/media/' . $storedFileName;
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO media_files (file_name, path, mime_type, file_size, alt_text, caption, created_at) VALUES (:file_name, :path, :mime_type, :file_size, :alt_text, :caption, :created_at)');
        PdoHelper::execute(statement: $stmt, params: [
            'file_name' => $this->safeOriginalName(fileName: $input->string(key: 'name', default: $storedFileName)),
            'path' => $path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'alt_text' => $altText === '' ? null : $altText,
            'caption' => $caption === '' ? null : $caption,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return new Upload\Output(uploaded: true);
    }

    public function delete(int $id): Delete\Output
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT path FROM media_files WHERE id = :id LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $path = PdoHelper::fetchColumn(statement: $stmt);
        if (!is_string($path) || $path === '') {
            return new Delete\Output(deleted: false, error: '削除対象のメディアが見つかりません。');
        }

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

        $filePath = $this->publicPath(path: $path);
        if ($filePath !== null && is_file($filePath)) {
            unlink($filePath);
        }

        return new Delete\Output(deleted: true);
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

    private function publicUploadDir(): string
    {
        return dirname(__DIR__, 5) . '/public/uploads/media';
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

        return mb_substr($fileName, 0, 190);
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
