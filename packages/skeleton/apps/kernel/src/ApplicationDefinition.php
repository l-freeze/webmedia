<?php

declare(strict_types=1);

namespace App\Kernel;

use InvalidArgumentException;

final readonly class ApplicationDefinition
{
    public function __construct(
        public string $name,
        public string $pathPrefix,
        public string $routesPath,
        public string $containerPath,
        public string $middlewarePath,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Application name must not be empty.');
        }

        if ($pathPrefix === '' || !str_starts_with($pathPrefix, '/')) {
            throw new InvalidArgumentException('Application path prefix must start with "/".');
        }

        if ($pathPrefix !== '/' && (str_ends_with($pathPrefix, '/') || str_contains($pathPrefix, '//'))) {
            throw new InvalidArgumentException('Application path prefix must be canonical, without empty segments or a trailing "/".');
        }
    }
}
