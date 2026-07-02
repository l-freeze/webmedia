<?php

declare(strict_types=1);

namespace Tests\Cms\Media;

use App\Cms\Infrastructure\Media\VipsImageVariantGenerator;
use App\Framework\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;

final class VipsImageVariantGeneratorTest extends TestCase
{
    public function testGenerateCreatesThumbnailAndOptimizedPathsWhenCommandSucceeds(): void
    {
        $directory = sys_get_temp_dir() . '/webmedia-vips-test-' . bin2hex(random_bytes(4));
        mkdir($directory, 0775, true);
        $binary = $directory . '/fake-vipsthumbnail';
        file_put_contents($binary, <<<'SH'
#!/bin/sh
output=""
while [ "$#" -gt 0 ]; do
  if [ "$1" = "-o" ]; then
    shift
    output="$1"
  fi
  shift
done
output="${output%%[*}"
printf 'webp' > "$output"
SH);
        chmod($binary, 0755);
        $source = $directory . '/sample.jpg';
        file_put_contents($source, 'jpg');

        $generator = new VipsImageVariantGenerator(processRunner: new ProcessRunner(), binary: $binary);
        $paths = $generator->generate(
            absolutePath: $source,
            publicPath: '/uploads/media/sample.jpg',
            mimeType: 'image/jpeg',
            width: 1200,
            height: 800,
        );

        self::assertSame('/uploads/media/sample-thumb.webp', $paths->thumbnailPath);
        self::assertSame('/uploads/media/sample-optimized.webp', $paths->optimizedPath);
        self::assertFileExists($directory . '/sample-thumb.webp');
        self::assertFileExists($directory . '/sample-optimized.webp');
    }

    public function testGenerateSkipsUnsupportedImageTypes(): void
    {
        $generator = new VipsImageVariantGenerator(processRunner: new ProcessRunner(), binary: '/missing-vipsthumbnail');
        $paths = $generator->generate(
            absolutePath: __FILE__,
            publicPath: '/uploads/media/sample.gif',
            mimeType: 'image/gif',
            width: 1200,
            height: 800,
        );

        self::assertNull($paths->thumbnailPath);
        self::assertNull($paths->optimizedPath);
    }

    public function testGenerateReturnsEmptyPathsWhenCommandCannotStart(): void
    {
        $directory = sys_get_temp_dir() . '/webmedia-vips-missing-test-' . bin2hex(random_bytes(4));
        mkdir($directory, 0775, true);
        $source = $directory . '/sample.jpg';
        file_put_contents($source, 'jpg');

        $generator = new VipsImageVariantGenerator(processRunner: new ProcessRunner(), binary: $directory . '/missing-vipsthumbnail');
        $paths = $generator->generate(
            absolutePath: $source,
            publicPath: '/uploads/media/sample.jpg',
            mimeType: 'image/jpeg',
            width: 1200,
            height: 800,
        );

        self::assertNull($paths->thumbnailPath);
        self::assertNull($paths->optimizedPath);
        self::assertFileDoesNotExist($directory . '/sample-thumb.webp');
        self::assertFileDoesNotExist($directory . '/sample-optimized.webp');
    }

    public function testGenerateSkipsOverLargeImages(): void
    {
        $generator = new VipsImageVariantGenerator(processRunner: new ProcessRunner(), binary: '/missing-vipsthumbnail');
        $paths = $generator->generate(
            absolutePath: __FILE__,
            publicPath: '/uploads/media/sample.jpg',
            mimeType: 'image/jpeg',
            width: 100000,
            height: 100000,
        );

        self::assertNull($paths->thumbnailPath);
        self::assertNull($paths->optimizedPath);
    }
}
