<?php

declare(strict_types=1);

namespace Pera\Framework\Process;

/**
 * Result of a synchronous external process execution.
 *
 * `exitCode === null` means the process did not produce a normal exit code,
 * usually because startup failed or the runner timeout was reached.
 *
 * `startupError !== null` means proc_open() did not start the process.
 *
 * `timedOut === true` means the runner timeout was reached; it does not
 * guarantee that every descendant process has been terminated.
 *
 *
 * 同期的な外部プロセス実行の結果を表します。
 *
 * `exitCode === null` は、起動失敗や timeout などにより通常の終了コードが
 * 確定しなかった状態を表します。
 *
 * `startupError !== null` は、proc_open() がプロセスを起動できなかった
 * 状態を表します。
 *
 * `timedOut === true` は runner の timeout に達したことを表します。
 * 外部コマンドがさらに起動した子孫プロセスまで完全に終了したことは保証しません。
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
