<?php

declare(strict_types=1);

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
 * composer.json without downloading packages or changing composer.lock.
 *
 * @param list<string> $features
 * @return array{
 *     required: array<string, string>,
 *     added: array<string, string>,
 *     present: array<string, string>,
 *     missingManifest: bool,
 *     messages: list<string>
 * }
 */
return static function (string $root, array $features) use ($requirementsByFeature): array {
    $required = [];
    foreach ($features as $feature) {
        if (!isset($requirementsByFeature[$feature])) {
            throw new InvalidArgumentException("Unknown scaffold dependency feature: {$feature}");
        }
        foreach ($requirementsByFeature[$feature] as $package => $constraint) {
            $required[$package] = $constraint;
        }
    }
    ksort($required);

    if ($required === []) {
        return [
            'required' => [],
            'added' => [],
            'present' => [],
            'missingManifest' => false,
            'messages' => [],
        ];
    }

    $composerPath = rtrim($root, '/\\') . '/composer.json';
    if (!is_file($composerPath)) {
        $requireArguments = [];
        $messages = ['composer.json was not found. Generated files require:'];
        foreach ($required as $package => $constraint) {
            $messages[] = "- {$package}: {$constraint}";
            $requireArguments[] = "{$package}:{$constraint}";
        }
        $messages[] = 'Add and install them from the project root:';
        $messages[] = 'composer require ' . implode(' ', $requireArguments);

        return [
            'required' => $required,
            'added' => [],
            'present' => [],
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

    $added = [];
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

    if ($added !== []) {
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

    return [
        'required' => $required,
        'added' => $added,
        'present' => $present,
        'missingManifest' => false,
        'messages' => $messages,
    ];
};
