<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMediaDerivativeFields extends AbstractMigration
{
    public function up(): void
    {
        $mediaFiles = $this->table('media_files');
        if (!$mediaFiles->hasColumn('width')) {
            $mediaFiles->addColumn('width', 'integer', ['null' => true])->update();
        }
        if (!$mediaFiles->hasColumn('height')) {
            $mediaFiles->addColumn('height', 'integer', ['null' => true])->update();
        }
        if (!$mediaFiles->hasColumn('thumbnail_path')) {
            $mediaFiles->addColumn('thumbnail_path', 'string', ['limit' => 255, 'null' => true])->update();
        }
        if (!$mediaFiles->hasColumn('optimized_path')) {
            $mediaFiles->addColumn('optimized_path', 'string', ['limit' => 255, 'null' => true])->update();
        }
    }

    public function down(): void
    {
        $mediaFiles = $this->table('media_files');
        if ($mediaFiles->hasColumn('optimized_path')) {
            $mediaFiles->removeColumn('optimized_path')->update();
        }
        if ($mediaFiles->hasColumn('thumbnail_path')) {
            $mediaFiles->removeColumn('thumbnail_path')->update();
        }
        if ($mediaFiles->hasColumn('height')) {
            $mediaFiles->removeColumn('height')->update();
        }
        if ($mediaFiles->hasColumn('width')) {
            $mediaFiles->removeColumn('width')->update();
        }
    }
}
