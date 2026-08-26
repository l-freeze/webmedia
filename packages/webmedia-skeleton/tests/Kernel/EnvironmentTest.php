<?php

declare(strict_types=1);

namespace Tests\Kernel;

use App\Kernel\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    private const KEY = 'WEBMEDIA_ENVIRONMENT_READER_TEST';

    private string|false $originalProcessValue;

    private bool $originalEnvExists;

    private mixed $originalEnvValue;

    private bool $originalServerExists;

    private mixed $originalServerValue;

    protected function setUp(): void
    {
        $this->originalProcessValue = getenv(self::KEY);
        $this->originalEnvExists = array_key_exists(self::KEY, $_ENV);
        $this->originalEnvValue = $_ENV[self::KEY] ?? null;
        $this->originalServerExists = array_key_exists(self::KEY, $_SERVER);
        $this->originalServerValue = $_SERVER[self::KEY] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalProcessValue === false) {
            putenv(self::KEY);
        } else {
            putenv(self::KEY . '=' . $this->originalProcessValue);
        }

        if ($this->originalEnvExists) {
            $_ENV[self::KEY] = $this->originalEnvValue;
        } else {
            unset($_ENV[self::KEY]);
        }

        if ($this->originalServerExists) {
            $_SERVER[self::KEY] = $this->originalServerValue;
        } else {
            unset($_SERVER[self::KEY]);
        }
    }

    public function testReadsProcessEnvironment(): void
    {
        putenv(self::KEY . '=process');
        unset($_ENV[self::KEY], $_SERVER[self::KEY]);

        self::assertSame('process', (new Environment())->string(key: self::KEY, default: 'default'));
    }

    public function testReadsDotenvPopulatedEnvironmentWhenProcessValueIsUnset(): void
    {
        putenv(self::KEY);
        $_ENV[self::KEY] = 'dotenv';
        $_SERVER[self::KEY] = 'server';

        self::assertSame('dotenv', (new Environment())->string(key: self::KEY, default: 'default'));
    }

    public function testProcessEnvironmentWinsWhenSourcesConflict(): void
    {
        putenv(self::KEY . '=process');
        $_ENV[self::KEY] = 'dotenv';
        $_SERVER[self::KEY] = 'server';

        self::assertSame('process', (new Environment())->string(key: self::KEY, default: 'default'));
    }

    public function testExplicitEmptyValueDoesNotFallThroughToAnotherSourceOrDefault(): void
    {
        putenv(self::KEY . '=');
        $_ENV[self::KEY] = 'dotenv';
        $_SERVER[self::KEY] = 'server';

        self::assertSame('', (new Environment())->string(key: self::KEY, default: 'default'));
    }

    public function testUsesDefaultOnlyWhenNoSourceDefinesTheKey(): void
    {
        putenv(self::KEY);
        unset($_ENV[self::KEY], $_SERVER[self::KEY]);

        self::assertSame('default', (new Environment())->string(key: self::KEY, default: 'default'));
    }
}
