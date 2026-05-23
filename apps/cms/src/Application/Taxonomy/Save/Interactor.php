<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Save;

use App\Cms\Domain\Repository\ITaxonomyRepository;

final readonly class Interactor
{
    public function __construct(private ITaxonomyRepository $taxonomy)
    {
    }

    public function execute(Input $input): Output
    {
        $this->taxonomy->createCategory($input->data);
        return new Output(true);
    }
}
