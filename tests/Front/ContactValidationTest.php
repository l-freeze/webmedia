<?php

declare(strict_types=1);

namespace Tests\Front;

use PHPUnit\Framework\TestCase;

final class ContactValidationTest extends TestCase
{
    public function testEmailValidationRejectsInvalidAddress(): void
    {
        self::assertFalse(filter_var('invalid-address', FILTER_VALIDATE_EMAIL));
    }

    public function testEmailValidationAcceptsValidAddress(): void
    {
        self::assertSame('reader@example.test', filter_var('reader@example.test', FILTER_VALIDATE_EMAIL));
    }
}
