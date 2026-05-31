<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Framework\Validation\FormValidator;
use App\Framework\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private IPageRepository $pages)
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

        return Output::saved(pageId: $this->pages->save(page: Page::fromInput(id: $input->id, data: $input->data)));
    }
}
