<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Save;

use App\Cms\Domain\Repository\ISettingRepository;
use Psr\SimpleCache\CacheInterface;

final readonly class Interactor
{
    public function __construct(
        private ISettingRepository $settings,
        private CacheInterface $cache,
    ) {
    }

    public function execute(Input $input): Output
    {
        $this->settings->save($input->data);
        $this->cache->delete(key: 'front.layout_globals.v2');

        return new Output(saved: true);
    }
}
