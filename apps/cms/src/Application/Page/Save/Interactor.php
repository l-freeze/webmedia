<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Save;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Repository\IPageRepository;
use App\Cms\Domain\Value\MetaDescription;
use App\Cms\Domain\Value\PageBody;
use App\Cms\Domain\Value\PageBodyFormat;
use App\Cms\Domain\Value\PageDisplayOrder;
use App\Cms\Domain\Value\PageId;
use App\Cms\Domain\Value\PageSlug;
use App\Cms\Domain\Value\PageStatus;
use App\Cms\Domain\Value\PageTitle;
use App\Cms\Domain\Value\SeoTitle;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private IPageRepository $pages)
    {
    }

    public function execute(Input $input): Output
    {
        $validation = $this->validate($input);
        if (!$validation->isValid()) {
            return Output::invalid($validation);
        }

        $validation = $this->validateSlugUniqueness($input);
        if (!$validation->isValid()) {
            return Output::invalid($validation);
        }

        $page = $this->createEntity($input);
        $pageId = $this->pages->save($page);

        return Output::saved($pageId);
    }

    private function validate(Input $input): ValidationOutput
    {
        return InputValidator::validate($input);
    }

    private function validateSlugUniqueness(Input $input): ValidationOutput
    {
        $slugExists = $this->pages->slugExists(slug: $input->slug, exceptId: $input->id);
        if ($slugExists) {
            return ValidationOutput::invalid(
                new ValidationError(field: 'slug', message: 'このスラッグは既に使われています。'),
            );
        }

        return ValidationOutput::valid();
    }

    private function createEntity(Input $input): Page
    {
        return new Page(
            id: $input->id === null ? null : new PageId(value: $input->id),
            title: new PageTitle(value: $input->title),
            slug: new PageSlug(value: $input->slug),
            body: new PageBody(value: $input->body),
            bodyFormat: new PageBodyFormat(value: $input->bodyFormat),
            status: new PageStatus(value: $input->status),
            displayOrder: new PageDisplayOrder(value: (int) $input->displayOrder),
            seoTitle: new SeoTitle(value: $input->seoTitle),
            metaDescription: new MetaDescription(value: $input->metaDescription),
        );
    }
}
