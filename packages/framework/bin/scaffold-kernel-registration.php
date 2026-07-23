<?php

declare(strict_types=1);

/**
 * Register an application in a standard Pera Kernel configuration.
 *
 * A consumer-owned custom Kernel remains valid: when the generated markers are
 * absent, this helper leaves it untouched and returns the manual definition.
 *
 * @return Closure(string, string, string, string): array{
 *     registered: bool,
 *     messages: list<string>
 * }
 */
return static function (
    string $root,
    string $name,
    string $pathPrefix,
    string $definition,
): array {
    $applicationPath = rtrim($root, '/\\') . '/apps/kernel/config/application.php';
    if (!is_file($applicationPath)) {
        return [
            'registered' => false,
            'messages' => [
                'Kernel application configuration was not found.',
                'Add this ApplicationDefinition to apps/kernel/config/application.php:',
                '',
                $definition,
            ],
        ];
    }

    $contents = file_get_contents($applicationPath);
    if (!is_string($contents)) {
        throw new RuntimeException("Failed to read Kernel application configuration: {$applicationPath}");
    }

    $startMarker = '        // <pera:applications>';
    $endMarker = '        // </pera:applications>';
    $startPosition = strpos($contents, $startMarker);
    $endPosition = strpos($contents, $endMarker);
    if ($startPosition === false || $endPosition === false || $startPosition >= $endPosition) {
        return [
            'registered' => false,
            'messages' => [
                'Custom Kernel configuration detected; it was left unchanged.',
                'Add this ApplicationDefinition to apps/kernel/config/application.php:',
                '',
                $definition,
            ],
        ];
    }
    if (!str_contains($contents, 'use App\\Kernel\\ApplicationDefinition;')) {
        throw new RuntimeException('Standard Kernel application configuration is missing the ApplicationDefinition import.');
    }

    $managedSection = substr(
        $contents,
        $startPosition,
        $endPosition + strlen($endMarker) - $startPosition,
    );
    if (
        str_contains($managedSection, "name: '{$name}'")
        || str_contains($managedSection, "pathPrefix: '{$pathPrefix}'")
    ) {
        throw new RuntimeException(
            "Kernel application name or path prefix is already registered: {$name}: {$pathPrefix}",
        );
    }

    $indentedDefinition = '';
    foreach (explode("\n", rtrim($definition)) as $line) {
        $indentedDefinition .= '        ' . $line . "\n";
    }
    $replacement = $indentedDefinition . $endMarker;
    $updatedContents = substr_replace(
        $contents,
        $replacement,
        $endPosition,
        strlen($endMarker),
    );

    $temporaryPath = $applicationPath . '.pera-' . bin2hex(random_bytes(6));
    try {
        if (file_put_contents($temporaryPath, $updatedContents, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write temporary Kernel application configuration: {$temporaryPath}");
        }
        if (!rename($temporaryPath, $applicationPath)) {
            throw new RuntimeException("Failed to replace Kernel application configuration: {$applicationPath}");
        }
    } finally {
        if (is_file($temporaryPath)) {
            unlink($temporaryPath);
        }
    }

    return [
        'registered' => true,
        'messages' => ["Registered application in apps/kernel/config/application.php: {$name}: {$pathPrefix}"],
    ];
};
