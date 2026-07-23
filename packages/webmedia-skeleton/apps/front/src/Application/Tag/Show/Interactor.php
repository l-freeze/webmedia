<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\Show;

use App\Front\Application\Tag\Show\Query\IArticleQuery;
use App\Front\Application\Tag\Show\Query\ITagQuery;

final readonly class Interactor
{
    public function __construct(private ITagQuery $tags, private IArticleQuery $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $tag = $this->tags->find($input->slug);
        $articles = $this->articles->articles($input->slug);

        return new Output(tag: $tag, articles: $articles);
    }
}
