<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Edit;

use App\Cms\Application\Setting\Edit\Query\ISettingQuery;

final readonly class Interactor
{
    public function __construct(private ISettingQuery $settings)
    {
    }

    public function execute(Input $input): Output
    {
        $settings = $this->settings->all();

        return new Output(settings: $settings);
    }
}
