<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Save;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationInput;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private IArticleRepository $articles)
    {
    }

    public function execute(Input $input): Output
    {
        $values = new ValidationInput(values: $input->data);
        $validation = (new FormValidator(input: $values))
            ->required(field: 'title', label: 'タイトル')
            ->required(field: 'slug', label: 'スラッグ')
            ->required(field: 'body_format', label: '入力種別')
            ->required(field: 'body_markdown', label: '本文')
            ->output();
        $validation = SlugValidator::validate(input: $values, validation: $validation);

        if (!$validation->isValid()) {
            return Output::invalid(validation: $validation);
        }

        if ($this->articles->slugExists(slug: $values->string(field: 'slug'), exceptId: $input->id)) {
            return Output::invalid(validation: ValidationOutput::invalid(
                new ValidationError(field: 'slug', message: 'このスラッグは既に使われています。'),
            ));
        }

        return Output::saved(articleId: $this->articles->save(article: Article::fromInput(id: $input->id, data: $input->data)));
    }
}
