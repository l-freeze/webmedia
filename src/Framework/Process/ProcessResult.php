<?php

declare(strict_types=1);

namespace App\Framework\Process;

/**
 * Result of a synchronous external process execution.
 *
 * `exitCode === null` means the process did not produce a normal exit code,
 * usually because startup failed or the runner timeout was reached.
 * `startupError !== null` means proc_open() did not start the process.
 * `timedOut === true` means the runner timeout was reached; it does not
 * guarantee that every descendant process has been terminated.
 */
final readonly class ProcessResult
{
    public function __construct(
        public ?int $exitCode,
        public string $stdout,
        public string $stderr,
        public bool $timedOut,
        public ?string $startupError = null,
    ) {
    }

    public function successful(): bool
    {
        return $this->startupError === null
            && !$this->timedOut
            && $this->exitCode === 0;
    }

    public function started(): bool
    {
        return $this->startupError === null;
    }

    public function failedToStart(): bool
    {
        return $this->startupError !== null;
    }
}
