<?php

declare(strict_types=1);

namespace App\Cms\Application\Trash\List;

use App\Cms\Application\Trash\List\Query\ITrashQuery;

final readonly class Interactor
{
    public function __construct(private ITrashQuery $trash)
    {
    }

    public function execute(Input $input): Output
    {
        $rows = $this->trash->list();

        return new Output(rows: $rows);
    }
}
