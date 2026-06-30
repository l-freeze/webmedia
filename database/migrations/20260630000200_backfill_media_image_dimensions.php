<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BackfillMediaImageDimensions extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->table('media_files')->hasColumn('width') || !$this->table('media_files')->hasColumn('height')) {
            return;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->fetchAll("SELECT id, path FROM media_files WHERE mime_type LIKE 'image/%' AND (width IS NULL OR height IS NULL)");
        foreach ($rows as $row) {
            $id = $this->intValue(value: $row['id'] ?? null);
            $path = $this->stringValue(value: $row['path'] ?? null);
            $filePath = $this->publicPath(path: $path);
            if ($id <= 0 || $filePath === null || !is_file($filePath)) {
                continue;
            }

            $size = @getimagesize($filePath);
            if (!is_array($size) || $size[0] <= 0 || $size[1] <= 0) {
                continue;
            }

            $this->execute(
                sprintf(
                    'UPDATE media_files SET width = %d, height = %d WHERE id = %d',
                    $size[0],
                    $size[1],
                    $id,
                ),
            );
        }
    }

    public function down(): void
    {
    }

    private function publicPath(string $path): ?string
    {
        if (!str_starts_with($path, '/uploads/media/')) {
            return null;
        }

        return dirname(__DIR__, 2) . '/public/uploads/media/' . basename($path);
    }

    private function intValue(mixed $value): int
    {
        return is_int($value) || is_float($value) || is_string($value) && is_numeric($value)
            ? (int) $value
            : 0;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
