<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Framework\Validation\FormValidator;
use App\Framework\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private IArticleRepository $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $validation = (new FormValidator(input: new ValidationInput(values: $input->data)))
            ->required(field: 'title', label: 'タイトル')
            ->required(field: 'slug', label: 'スラッグ')
            ->required(field: 'body_markdown', label: '本文')
            ->output();

        if (!$validation->isValid()) {
            return Output::invalid(validation: $validation);
        }

        return Output::saved(articleId: $this->articles->save(article: Article::fromInput(id: $input->id, data: $input->data)));
    }
}
