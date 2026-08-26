<?php

declare(strict_types=1);

namespace Tests\Cms\Media;

use App\Cms\Infrastructure\Media\VipsImageVariantGenerator;
use App\Cms\Infrastructure\Persistence\PdoMediaRepository;
use PDO;
use Pera\Framework\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use Stringable;
use Throwable;

final class PdoMediaRepositoryTest extends TestCase
{
    public function testUnreadableImageDimensionsAreLoggedBeforeApplicationContinues(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'webmedia-invalid-image-');
        self::assertIsString($path);
        self::assertNotFalse(file_put_contents($path, 'not an image'));
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
            public array $records = [];

            /** @param array<string, mixed> $context */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };
        $repository = new PdoMediaRepository(
            pdo: new class extends PDO {
                public function __construct()
                {
                }
            },
            imageVariants: new VipsImageVariantGenerator(processRunner: new ProcessRunner()),
            logger: $logger,
        );
        $method = new ReflectionMethod(PdoMediaRepository::class, 'imageDimensions');

        try {
            $dimensions = $method->invoke($repository, $path, 'image/jpeg');

            self::assertSame([null, null], $dimensions);
            self::assertCount(1, $logger->records);
            self::assertSame('warning', $logger->records[0]['level']);
            self::assertSame('Uploaded image dimensions could not be read.', $logger->records[0]['message']);
            self::assertSame('image/jpeg', $logger->records[0]['context']['mime_type'] ?? null);
            $exception = $logger->records[0]['context']['exception'] ?? null;
            self::assertInstanceOf(Throwable::class, $exception);
            self::assertNotSame('', $exception->getMessage());
        } finally {
            unlink($path);
        }
    }
}
