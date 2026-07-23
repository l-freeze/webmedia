<?php

declare(strict_types=1);

namespace Tests\Cms\Application;

use App\Cms\Application\Article\Save\Input as ArticleInput;
use App\Cms\Application\Article\Save\InputValidator as ArticleInputValidator;
use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;
use App\Cms\Application\Page\Save\Input as PageInput;
use App\Cms\Application\Page\Save\InputValidator as PageInputValidator;
use PHPUnit\Framework\TestCase;

final class ContentEnumInputValidatorTest extends TestCase
{
    public function testArticleRejectsUnknownStatusAndBodyFormat(): void
    {
        $validation = ArticleInputValidator::validate(ArticleInput::create(
            id: null,
            currentUser: new CurrentCmsUser(id: 1, email: 'admin@example.test', name: 'Admin', role: 'admin'),
            title: 'Title',
            slug: 'slug',
            bodyFormat: 'plain',
            body: 'Body',
            excerpt: '',
            status: 'scheduled',
            categoryId: '',
            eyecatchPath: '',
            publishedAt: '',
            publishedEndsAt: '',
            seoTitle: '',
            metaDescription: '',
        ));

        self::assertSame(['body_format', 'status'], array_column($validation->errors, 'field'));
    }

    public function testPageRejectsUnknownStatusAndBodyFormat(): void
    {
        $validation = PageInputValidator::validate(PageInput::create(
            id: null,
            title: 'Title',
            slug: 'slug',
            bodyFormat: 'plain',
            body: 'Body',
            status: 'scheduled',
            displayOrder: '0',
            seoTitle: '',
            metaDescription: '',
        ));

        self::assertSame(['body_format', 'status'], array_column($validation->errors, 'field'));
    }
}
