<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Process\ProcessRunner;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProcessRunnerTest extends TestCase
{
    public function testRunReturnsSuccessfulResultWithStdoutAndStderr(): void
    {
        $result = (new ProcessRunner())->run(
            command: [PHP_BINARY, '-r', 'fwrite(STDOUT, "out"); fwrite(STDERR, "err");'],
            timeoutSeconds: 5,
        );

        self::assertTrue($result->successful());
        self::assertTrue($result->started());
        self::assertFalse($result->failedToStart());
        self::assertSame(0, $result->exitCode);
        self::assertSame('out', $result->stdout);
        self::assertSame('err', $result->stderr);
        self::assertFalse($result->timedOut);
        self::assertNull($result->startupError);
    }

    public function testRunReturnsNonSuccessfulResultForNonZeroExitCode(): void
    {
        $result = (new ProcessRunner())->run(
            command: [PHP_BINARY, '-r', 'fwrite(STDERR, "failed"); exit(7);'],
            timeoutSeconds: 5,
        );

        self::assertFalse($result->successful());
        self::assertSame(7, $result->exitCode);
        self::assertSame('failed', $result->stderr);
        self::assertFalse($result->timedOut);
    }

    public function testRunCapturesStartupErrorWithoutLeakingWarning(): void
    {
        $capturedWarnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$capturedWarnings): bool {
            $capturedWarnings[] = $message;
            return true;
        });
        try {
            $result = (new ProcessRunner())->run(
                command: ['/tmp/webmedia-missing-command-' . bin2hex(random_bytes(4))],
                timeoutSeconds: 5,
            );
        } finally {
            restore_error_handler();
        }

        self::assertFalse($result->successful());
        self::assertFalse($result->started());
        self::assertTrue($result->failedToStart());
        self::assertNull($result->exitCode);
        self::assertNotNull($result->startupError);
        self::assertSame([], $capturedWarnings);
    }

    public function testRunTimesOutAndReturnsPartialOutput(): void
    {
        $result = (new ProcessRunner())->run(
            command: [PHP_BINARY, '-r', 'fwrite(STDOUT, "started"); sleep(3);'],
            timeoutSeconds: 1,
            terminateGraceSeconds: 0,
        );

        self::assertFalse($result->successful());
        self::assertTrue($result->timedOut);
        self::assertSame('started', $result->stdout);
    }

    public function testRunLimitsStdoutAndStderrIndependently(): void
    {
        $result = (new ProcessRunner())->run(
            command: [PHP_BINARY, '-r', 'fwrite(STDOUT, "abcdef"); fwrite(STDERR, "uvwxyz");'],
            timeoutSeconds: 5,
            maxStdoutBytes: 3,
            maxStderrBytes: 2,
        );

        self::assertTrue($result->successful());
        self::assertSame('abc', $result->stdout);
        self::assertSame('uv', $result->stderr);
    }

    public function testRunUsesWorkingDirectoryAndEnvironment(): void
    {
        $directory = sys_get_temp_dir();
        $result = (new ProcessRunner())->run(
            command: [PHP_BINARY, '-r', 'echo getcwd() . "|" . getenv("PROCESS_RUNNER_TEST");'],
            timeoutSeconds: 5,
            cwd: $directory,
            env: ['PROCESS_RUNNER_TEST' => 'ok'],
        );

        self::assertTrue($result->successful());
        self::assertSame($directory . '|ok', $result->stdout);
    }

    public function testRunRejectsEmptyCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProcessRunner())->run(command: [], timeoutSeconds: 5);
    }

    public function testRunRejectsNonListCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var array<int, string> $command */
        $command = [1 => PHP_BINARY];
        (new ProcessRunner())->run(command: $command, timeoutSeconds: 5);
    }

    public function testRunRejectsEmptyCommandElement(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProcessRunner())->run(command: [PHP_BINARY, ''], timeoutSeconds: 5);
    }

    public function testRunRejectsNonStringCommandElement(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProcessRunner())->run(command: [PHP_BINARY, 123], timeoutSeconds: 5);
    }

    public function testRunRejectsInvalidTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProcessRunner())->run(command: [PHP_BINARY], timeoutSeconds: 0);
    }

    public function testRunRejectsNegativeOutputLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProcessRunner())->run(command: [PHP_BINARY], timeoutSeconds: 5, maxStdoutBytes: -1);
    }

    public function testRunRejectsMissingWorkingDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProcessRunner())->run(
            command: [PHP_BINARY],
            timeoutSeconds: 5,
            cwd: sys_get_temp_dir() . '/missing-process-runner-dir-' . bin2hex(random_bytes(4)),
        );
    }
}
