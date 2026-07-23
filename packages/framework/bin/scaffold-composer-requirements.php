<?php

declare(strict_types=1);

/** @var array<string, array<string, string>> $requirementsByFeature */
$requirementsByFeature = [
    'dotenv' => ['vlucas/phpdotenv' => '^5.6'],
    'latte' => ['latte/latte' => '^3.0'],
    'pdo-sqlite' => [
        'ext-pdo' => '*',
        'ext-pdo_sqlite' => '*',
    ],
];

/**
 * Add runtime dependencies used by generated files to the consumer project's
 * composer.json and register generated PSR-4 source roots without downloading
 * packages or changing composer.lock.
 *
 * @param list<string> $features
 * @param array<string, string> $psr4
 * @return array{
 *     required: array<string, string>,
 *     added: array<string, string>,
 *     present: array<string, string>,
 *     autoloadRequired: array<string, string>,
 *     autoloadAdded: array<string, string>,
 *     autoloadPresent: array<string, string>,
 *     missingManifest: bool,
 *     messages: list<string>
 * }
 */
return static function (string $root, array $features, array $psr4 = []) use ($requirementsByFeature): array {
    /** @var array<string, string> $required */
    $required = [];
    foreach ($features as $feature) {
        if (!is_string($feature)) {
            throw new InvalidArgumentException('Scaffold dependency feature must be a string.');
        }
        if (!isset($requirementsByFeature[$feature])) {
            throw new InvalidArgumentException("Unknown scaffold dependency feature: {$feature}");
        }
        foreach ($requirementsByFeature[$feature] as $package => $constraint) {
            $required[$package] = $constraint;
        }
    }
    ksort($required);

    /** @var array<string, string> $autoloadRequired */
    $autoloadRequired = [];
    foreach ($psr4 as $namespace => $path) {
        if (
            !is_string($namespace)
            || $namespace === ''
            || !str_ends_with($namespace, '\\')
            || !is_string($path)
            || $path === ''
            || str_starts_with($path, '/')
        ) {
            throw new InvalidArgumentException('Scaffold PSR-4 entries must use non-empty namespace prefixes and relative paths.');
        }
        $autoloadRequired[$namespace] = $path;
    }
    ksort($autoloadRequired);

    if ($required === [] && $autoloadRequired === []) {
        return [
            'required' => [],
            'added' => [],
            'present' => [],
            'autoloadRequired' => [],
            'autoloadAdded' => [],
            'autoloadPresent' => [],
            'missingManifest' => false,
            'messages' => [],
        ];
    }

    $composerPath = rtrim($root, '/\\') . '/composer.json';
    if (!is_file($composerPath)) {
        /** @var list<string> $requireArguments */
        $requireArguments = [];
        /** @var list<string> $messages */
        $messages = ['composer.json was not found.'];
        if ($required !== []) {
            $messages[] = 'Generated files require:';
            foreach ($required as $package => $constraint) {
                $messages[] = "- {$package}: {$constraint}";
                $requireArguments[] = "{$package}:{$constraint}";
            }
            $messages[] = 'Add and install them from the project root:';
            $messages[] = 'composer require ' . implode(' ', $requireArguments);
        }
        if ($autoloadRequired !== []) {
            $messages[] = 'Add these PSR-4 entries to the project Composer manifest:';
            foreach ($autoloadRequired as $namespace => $path) {
                $messages[] = "- {$namespace}: {$path}";
            }
        }

        return [
            'required' => $required,
            'added' => [],
            'present' => [],
            'autoloadRequired' => $autoloadRequired,
            'autoloadAdded' => [],
            'autoloadPresent' => [],
            'missingManifest' => true,
            'messages' => $messages,
        ];
    }

    $contents = file_get_contents($composerPath);
    if (!is_string($contents)) {
        throw new RuntimeException("Failed to read Composer manifest: {$composerPath}");
    }

    try {
        $composer = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException(
            "Composer manifest is not valid JSON: {$composerPath}: {$exception->getMessage()}",
            previous: $exception,
        );
    }
    if (!$composer instanceof stdClass) {
        throw new RuntimeException("Composer manifest root must be an object: {$composerPath}");
    }

    $require = $composer->require ?? new stdClass();
    if (!$require instanceof stdClass) {
        throw new RuntimeException("Composer manifest require must be an object: {$composerPath}");
    }
    $requireDev = $composer->{'require-dev'} ?? new stdClass();
    if (!$requireDev instanceof stdClass) {
        throw new RuntimeException("Composer manifest require-dev must be an object: {$composerPath}");
    }
    $autoload = $composer->autoload ?? new stdClass();
    if (!$autoload instanceof stdClass) {
        throw new RuntimeException("Composer manifest autoload must be an object: {$composerPath}");
    }
    $psr4Config = $autoload->{'psr-4'} ?? new stdClass();
    if (!$psr4Config instanceof stdClass) {
        throw new RuntimeException("Composer manifest autoload.psr-4 must be an object: {$composerPath}");
    }

    /** @var array<string, string> $added */
    $added = [];
    /** @var array<string, string> $present */
    $present = [];
    foreach ($required as $package => $defaultConstraint) {
        if (property_exists($require, $package)) {
            $constraint = $require->{$package};
            if (!is_string($constraint)) {
                throw new RuntimeException("Composer runtime requirement must be a string: {$package}");
            }
            $present[$package] = $constraint;
            continue;
        }

        $constraint = $defaultConstraint;
        if (property_exists($requireDev, $package)) {
            $developmentConstraint = $requireDev->{$package};
            if (!is_string($developmentConstraint)) {
                throw new RuntimeException("Composer development requirement must be a string: {$package}");
            }
            $constraint = $developmentConstraint;
            unset($requireDev->{$package});
        }

        $require->{$package} = $constraint;
        $added[$package] = $constraint;
    }

    /** @var array<string, string> $autoloadAdded */
    $autoloadAdded = [];
    /** @var array<string, string> $autoloadPresent */
    $autoloadPresent = [];
    foreach ($autoloadRequired as $namespace => $path) {
        if (property_exists($psr4Config, $namespace)) {
            $existingPath = $psr4Config->{$namespace};
            if (!is_string($existingPath)) {
                throw new RuntimeException("Composer PSR-4 path must be a string: {$namespace}");
            }
            if ($existingPath !== $path) {
                throw new RuntimeException(
                    "Composer PSR-4 namespace is already mapped to a different path: {$namespace}: {$existingPath}",
                );
            }
            $autoloadPresent[$namespace] = $existingPath;
            continue;
        }

        $psr4Config->{$namespace} = $path;
        $autoloadAdded[$namespace] = $path;
    }

    if ($added !== [] || $autoloadAdded !== []) {
        $runtimeRequirements = get_object_vars($require);
        ksort($runtimeRequirements);
        $composer->require = (object) $runtimeRequirements;

        $developmentRequirements = get_object_vars($requireDev);
        if ($developmentRequirements === []) {
            unset($composer->{'require-dev'});
        } else {
            ksort($developmentRequirements);
            $composer->{'require-dev'} = (object) $developmentRequirements;
        }

        $psr4Entries = get_object_vars($psr4Config);
        ksort($psr4Entries);
        $autoload->{'psr-4'} = (object) $psr4Entries;
        $composer->autoload = $autoload;

        $encoded = json_encode(
            $composer,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
        $indentSize = 4;
        if (preg_match('/\n( +)"/', $contents, $indentMatch) === 1) {
            $indentSize = strlen($indentMatch[1]);
        }
        if ($indentSize !== 4) {
            $encoded = preg_replace_callback(
                '/^( +)/m',
                static fn (array $match): string => str_repeat(' ', intdiv(strlen($match[1]), 4) * $indentSize),
                $encoded,
            );
            if (!is_string($encoded)) {
                throw new RuntimeException("Failed to format Composer manifest: {$composerPath}");
            }
        }

        $temporaryPath = $composerPath . '.pera-' . bin2hex(random_bytes(6));
        try {
            if (file_put_contents($temporaryPath, $encoded . "\n", LOCK_EX) === false) {
                throw new RuntimeException("Failed to write temporary Composer manifest: {$temporaryPath}");
            }
            if (!rename($temporaryPath, $composerPath)) {
                throw new RuntimeException("Failed to replace Composer manifest: {$composerPath}");
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /** @var list<string> $messages */
    $messages = [];
    if ($added !== []) {
        $messages[] = 'Updated composer.json runtime requirements:';
        foreach ($added as $package => $constraint) {
            $messages[] = "- {$package}: {$constraint}";
        }
        $messages[] = 'Update composer.lock and install the new requirements:';
        $messages[] = is_file(rtrim($root, '/\\') . '/composer.lock')
            ? 'composer update ' . implode(' ', array_keys($added))
            : 'composer update';
    }
    if ($autoloadAdded !== []) {
        $messages[] = 'Updated composer.json PSR-4 autoload:';
        foreach ($autoloadAdded as $namespace => $path) {
            $messages[] = "- {$namespace}: {$path}";
        }
        if ($added === []) {
            $messages[] = 'Regenerate Composer autoload files:';
            $messages[] = 'composer dump-autoload';
        }
    }

    return [
        'required' => $required,
        'added' => $added,
        'present' => $present,
        'autoloadRequired' => $autoloadRequired,
        'autoloadAdded' => $autoloadAdded,
        'autoloadPresent' => $autoloadPresent,
        'missingManifest' => false,
        'messages' => $messages,
    ];
};
