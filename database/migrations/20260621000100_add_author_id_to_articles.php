<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAuthorIdToArticles extends AbstractMigration
{
    public function change(): void
    {
        $this->table('articles')
            ->addColumn('author_id', 'integer', ['null' => true, 'after' => 'author_name'])
            ->addIndex(['author_id'])
            ->update();
    }
}
