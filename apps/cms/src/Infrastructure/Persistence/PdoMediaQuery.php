<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Media;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoMediaQuery implements Media\List\Query\IMediaQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Media\List\Value\Media> */
    public function list(): array
    {
        $media = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, file_name, path, mime_type, file_size, width, height, thumbnail_path, optimized_path, alt_text, caption, created_at FROM media_files ORDER BY created_at DESC')) as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $path = $pdoRow->string(key: 'path');

            $media[] = new Media\List\Value\Media(
                id: $pdoRow->int(key: 'id'),
                fileName: $pdoRow->string(key: 'file_name'),
                path: $path,
                mimeType: $pdoRow->string(key: 'mime_type'),
                fileSize: $pdoRow->int(key: 'file_size'),
                width: $pdoRow->nullableInt(key: 'width'),
                height: $pdoRow->nullableInt(key: 'height'),
                thumbnailPath: $pdoRow->nullableString(key: 'thumbnail_path'),
                optimizedPath: $pdoRow->nullableString(key: 'optimized_path'),
                altText: $pdoRow->string(key: 'alt_text'),
                caption: $pdoRow->string(key: 'caption'),
                createdAt: $pdoRow->string(key: 'created_at'),
                usageLabels: $this->usageLabels(path: $path),
            );
        }

        return $media;
    }

    /** @return array<int, string> */
    private function usageLabels(string $path): array
    {
        if ($path === '') {
            return [];
        }

        $labels = [];
        foreach ($this->articleEyecatchUsages(path: $path) as $label) {
            $labels[] = $label;
        }
        foreach ($this->articleBodyUsages(path: $path) as $label) {
            $labels[] = $label;
        }
        foreach ($this->pageBodyUsages(path: $path) as $label) {
            $labels[] = $label;
        }
        foreach ($this->settingUsages(path: $path) as $label) {
            $labels[] = $label;
        }

        return array_values(array_unique($labels));
    }

    /** @return array<int, string> */
    private function articleEyecatchUsages(string $path): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, title FROM articles WHERE eyecatch_path = :path ORDER BY id DESC LIMIT 5');
        PdoHelper::execute(statement: $stmt, params: ['path' => $path]);

        return array_map(
            fn (array $row): string => '記事アイキャッチ #' . PdoRow::from(values: $row)->int(key: 'id') . ' ' . PdoRow::from(values: $row)->string(key: 'title'),
            PdoHelper::fetchAll(statement: $stmt),
        );
    }

    /** @return array<int, string> */
    private function articleBodyUsages(string $path): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, title FROM articles WHERE body LIKE :needle OR rendered_body_html LIKE :needle ORDER BY id DESC LIMIT 5');
        PdoHelper::execute(statement: $stmt, params: ['needle' => '%' . $path . '%']);

        return array_map(
            fn (array $row): string => '記事本文 #' . PdoRow::from(values: $row)->int(key: 'id') . ' ' . PdoRow::from(values: $row)->string(key: 'title'),
            PdoHelper::fetchAll(statement: $stmt),
        );
    }

    /** @return array<int, string> */
    private function pageBodyUsages(string $path): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, title FROM pages WHERE body LIKE :needle OR rendered_body_html LIKE :needle ORDER BY id DESC LIMIT 5');
        PdoHelper::execute(statement: $stmt, params: ['needle' => '%' . $path . '%']);

        return array_map(
            fn (array $row): string => '固定ページ本文 #' . PdoRow::from(values: $row)->int(key: 'id') . ' ' . PdoRow::from(values: $row)->string(key: 'title'),
            PdoHelper::fetchAll(statement: $stmt),
        );
    }

    /** @return array<int, string> */
    private function settingUsages(string $path): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT hero_image FROM site_settings WHERE hero_image = :path OR hero_image LIKE :needle LIMIT 5');
        PdoHelper::execute(statement: $stmt, params: ['path' => $path, 'needle' => '%' . $path . '%']);

        return array_map(
            fn (array $row): string => 'サイト設定 hero_image',
            PdoHelper::fetchAll(statement: $stmt),
        );
    }
}
