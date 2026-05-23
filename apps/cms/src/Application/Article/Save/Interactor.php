<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;

final readonly class Interactor
{
    public function __construct(private IArticleRepository $articles)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output($this->articles->save(Article::fromInput($input->id, $input->data)));
    }
}
