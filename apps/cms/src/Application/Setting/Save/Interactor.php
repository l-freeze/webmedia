<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Save;

use App\Cms\Domain\Repository\ISettingRepository;

final readonly class Interactor
{
    public function __construct(private ISettingRepository $settings)
    {
    }

    public function execute(Input $input): Output
    {
        $this->settings->save(data: $input->data);
        return new Output(saved: true);
    }
}
