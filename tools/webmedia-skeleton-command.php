<?php

declare(strict_types=1);

require __DIR__ . '/WebmediaSkeletonBuilder.php';

$action = $argv[1] ?? '';
$builder = new WebmediaSkeletonBuilder();

try {
    if ($action === 'generate') {
        $sourceRoot = $argv[2] ?? '';
        $destinationRoot = $argv[3] ?? '';
        $manifestPath = $argv[4] ?? '';
        $manifest = file_get_contents($manifestPath);
        if (!is_string($manifest)) {
            throw new RuntimeException("Tracked-file manifest was not found: {$manifestPath}");
        }
        $trackedPaths = [];
        foreach (preg_split('/\r?\n/', $manifest) ?: [] as $path) {
            if ($path !== '') {
                $trackedPaths[] = $path;
            }
        }
        $builder->generate(
            sourceRoot: $sourceRoot,
            destinationRoot: $destinationRoot,
            trackedPaths: $trackedPaths,
        );
        echo "Prepared Webmedia Skeleton candidate: {$destinationRoot}\n";
        exit(0);
    }

    if ($action === 'compare') {
        $expectedRoot = $argv[2] ?? '';
        $actualRoot = $argv[3] ?? '';
        $differences = $builder->differences(expectedRoot: $expectedRoot, actualRoot: $actualRoot);
        if ($differences !== []) {
            fwrite(STDERR, "Webmedia Skeleton is not synchronized with the canonical project:\n");
            foreach ($differences as $difference) {
                fwrite(STDERR, "- {$difference}\n");
            }
            exit(1);
        }
        echo "Webmedia Skeleton matches the canonical project.\n";
        exit(0);
    }

    if ($action === 'publish') {
        $candidateRoot = $argv[2] ?? '';
        $targetRoot = $argv[3] ?? '';
        $builder->publish(candidateRoot: $candidateRoot, targetRoot: $targetRoot);
        echo "Published verified Webmedia Skeleton: {$targetRoot}\n";
        exit(0);
    }

    fwrite(STDERR, "Internal usage: php tools/webmedia-skeleton-command.php generate|compare|publish ...\n");
    exit(1);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . "\n");
    exit(1);
}
