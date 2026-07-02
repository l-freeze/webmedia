<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Media;

use App\Framework\Process\ProcessRunner;

final readonly class VipsImageVariantGenerator
{
    private const THUMBNAIL_SIZE = '480x480';
    private const OPTIMIZED_SIZE = '1600x1600';
    private const THUMBNAIL_QUALITY = 78;
    private const OPTIMIZED_QUALITY = 84;
    private const COMMAND_TIMEOUT_SECONDS = 20;
    private const MAX_SOURCE_PIXELS = 50000000;

    /** @var array<string, true> */
    private const SUPPORTED_MIME_TYPES = [
        'image/jpeg' => true,
        'image/png' => true,
        'image/webp' => true,
    ];

    public function __construct(private ProcessRunner $processRunner, private string $binary = 'vipsthumbnail')
    {
    }

    public function generate(string $absolutePath, string $publicPath, string $mimeType, ?int $width, ?int $height): ImageVariantPaths
    {
        if (!isset(self::SUPPORTED_MIME_TYPES[$mimeType]) || !is_file($absolutePath)) {
            return new ImageVariantPaths();
        }

        if ($width !== null && $height !== null && $width * $height > self::MAX_SOURCE_PIXELS) {
            return new ImageVariantPaths();
        }

        $thumbnailPath = $this->variantPublicPath(publicPath: $publicPath, suffix: 'thumb');
        $optimizedPath = $this->variantPublicPath(publicPath: $publicPath, suffix: 'optimized');
        $thumbnailAbsolutePath = $this->variantAbsolutePath(absolutePath: $absolutePath, suffix: 'thumb');
        $optimizedAbsolutePath = $this->variantAbsolutePath(absolutePath: $absolutePath, suffix: 'optimized');

        return new ImageVariantPaths(
            thumbnailPath: $this->generateWebp(
                source: $absolutePath,
                destination: $thumbnailAbsolutePath,
                size: self::THUMBNAIL_SIZE,
                quality: self::THUMBNAIL_QUALITY,
            ) ? $thumbnailPath : null,
            optimizedPath: $this->generateWebp(
                source: $absolutePath,
                destination: $optimizedAbsolutePath,
                size: self::OPTIMIZED_SIZE,
                quality: self::OPTIMIZED_QUALITY,
            ) ? $optimizedPath : null,
        );
    }

    private function generateWebp(string $source, string $destination, string $size, int $quality): bool
    {
        $temporaryDestination = $destination . '.part.webp';
        $this->removeIfExists(path: $temporaryDestination);

        $result = $this->run(command: [
            $this->binary,
            $source,
            '--size',
            'down',
            '-s',
            $size,
            '-o',
            $temporaryDestination . '[Q=' . $quality . ',strip]',
        ]);

        if (!$result || !is_file($temporaryDestination)) {
            $this->removeIfExists(path: $temporaryDestination);
            return false;
        }

        if (is_file($destination)) {
            $this->removeIfExists(path: $destination);
        }

        return rename($temporaryDestination, $destination);
    }

    /** @param list<string> $command */
    private function run(array $command): bool
    {
        $result = $this->processRunner->run(
            command: $command,
            timeoutSeconds: self::COMMAND_TIMEOUT_SECONDS,
            maxStdoutBytes: 0,
            maxStderrBytes: 0,
        );

        return $result->successful();
    }

    private function variantPublicPath(string $publicPath, string $suffix): string
    {
        return preg_replace('/\.[^.\/]+$/', '-' . $suffix . '.webp', $publicPath) ?? ($publicPath . '-' . $suffix . '.webp');
    }

    private function variantAbsolutePath(string $absolutePath, string $suffix): string
    {
        return preg_replace('/\.[^.\/]+$/', '-' . $suffix . '.webp', $absolutePath) ?? ($absolutePath . '-' . $suffix . '.webp');
    }

    private function removeIfExists(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
