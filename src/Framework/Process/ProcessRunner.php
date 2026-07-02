<?php

declare(strict_types=1);

namespace App\Framework\Process;

use InvalidArgumentException;

final readonly class ProcessRunner
{
    private const DEFAULT_MAX_STDOUT_BYTES = 1048576;
    private const DEFAULT_MAX_STDERR_BYTES = 1048576;
    private const DEFAULT_TERMINATE_GRACE_SECONDS = 2;
    private const POLL_INTERVAL_MICROSECONDS = 100000;
    private const KILL_GRACE_SECONDS = 1;
    private const SIGTERM_FALLBACK = 15;
    private const SIGKILL_FALLBACK = 9;

    /**
     * Runs an external process synchronously.
     *
     * The command must be a list of arguments, not a shell command string. It
     * is passed to proc_open() as an array command, so PHP opens the process
     * directly without invoking a shell. Callers must keep the executable
     * fixed or choose it from an explicit allowlist.
     *
     * Startup warnings from proc_open() are captured as ProcessResult::startupError
     * without using @ suppression. Process failures are returned as normal
     * results; only invalid runner arguments throw InvalidArgumentException.
     *
     * Timeout cleanup uses best-effort terminate / kill / close. A timeout does
     * not guarantee that an external process group or descendant processes were
     * fully terminated.
     *
     *
     * 外部プロセスを同期的に実行します。
     *
     * command は shell command string ではなく、引数の list として渡します。
     * proc_open() には array command として渡すため、shell を起動せずに
     * PHP が直接プロセスを開きます。呼び出し側は executable を固定するか、
     * 明示的な allowlist から選んでください。
     *
     * proc_open() 起動時の warning は @ で抑制せず、
     * ProcessResult::startupError として捕捉します。外部プロセスの失敗は
     * 通常の結果として返し、runner 引数の validation failure だけ
     * InvalidArgumentException を投げます。
     *
     * timeout 時の cleanup は terminate / kill / close の best effort です。
     * timeout した場合でも、外部 process group や子孫プロセスが完全に
     * 終了したことは保証しません。
     *
     * @param array<int, mixed> $command
     * @param array<string, string>|null $env
     */
    public function run(
        array $command,
        int $timeoutSeconds,
        ?string $cwd = null,
        ?array $env = null,
        int $maxStdoutBytes = self::DEFAULT_MAX_STDOUT_BYTES,
        int $maxStderrBytes = self::DEFAULT_MAX_STDERR_BYTES,
        int $terminateGraceSeconds = self::DEFAULT_TERMINATE_GRACE_SECONDS,
    ): ProcessResult {
        $this->validate(
            command: $command,
            timeoutSeconds: $timeoutSeconds,
            cwd: $cwd,
            maxStdoutBytes: $maxStdoutBytes,
            maxStderrBytes: $maxStderrBytes,
            terminateGraceSeconds: $terminateGraceSeconds,
        );
        /** @var list<string> $command */

        $pipes = [];
        $startupError = null;
        set_error_handler(static function (int $severity, string $message) use (&$startupError): bool {
            $startupError = $message;
            return true;
        });
        try {
            $process = proc_open(
                command: $command,
                descriptor_spec: [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                pipes: $pipes,
                cwd: $cwd,
                env_vars: $env,
            );
        } finally {
            restore_error_handler();
        }

        if (!is_resource($process)) {
            $this->closePipes(pipes: $pipes);
            return new ProcessResult(
                exitCode: null,
                stdout: '',
                stderr: '',
                timedOut: false,
                startupError: $startupError ?? 'Failed to start process.',
            );
        }

        if (isset($pipes[0]) && is_resource($pipes[0])) {
            fclose($pipes[0]);
        }
        if (isset($pipes[1]) && is_resource($pipes[1])) {
            stream_set_blocking($pipes[1], false);
        }
        if (isset($pipes[2]) && is_resource($pipes[2])) {
            stream_set_blocking($pipes[2], false);
        }

        $stdout = '';
        $stderr = '';
        $startedAt = microtime(true);
        $timedOut = false;

        do {
            $stdout = $this->appendOutput(output: $stdout, pipe: $pipes[1] ?? null, maxBytes: $maxStdoutBytes);
            $stderr = $this->appendOutput(output: $stderr, pipe: $pipes[2] ?? null, maxBytes: $maxStderrBytes);
            $status = proc_get_status($process);
            if ($status['running'] !== true) {
                break;
            }

            if ((microtime(true) - $startedAt) >= $timeoutSeconds) {
                $timedOut = true;
                $this->terminate(process: $process, graceSeconds: $terminateGraceSeconds);
                break;
            }

            usleep(self::POLL_INTERVAL_MICROSECONDS);
        } while (true);

        $stdout = $this->appendOutput(output: $stdout, pipe: $pipes[1] ?? null, maxBytes: $maxStdoutBytes);
        $stderr = $this->appendOutput(output: $stderr, pipe: $pipes[2] ?? null, maxBytes: $maxStderrBytes);
        $this->closePipes(pipes: $pipes);

        $exitCode = proc_close($process);

        return new ProcessResult(
            exitCode: $exitCode === -1 ? null : $exitCode,
            stdout: $stdout,
            stderr: $stderr,
            timedOut: $timedOut,
        );
    }

    /**
     * @param array<int, mixed> $command
     */
    private function validate(
        array $command,
        int $timeoutSeconds,
        ?string $cwd,
        int $maxStdoutBytes,
        int $maxStderrBytes,
        int $terminateGraceSeconds,
    ): void {
        if ($command === []) {
            throw new InvalidArgumentException('Process command must not be empty.');
        }
        if (!array_is_list($command)) {
            throw new InvalidArgumentException('Process command must be a list of strings.');
        }
        foreach ($command as $element) {
            if (!is_string($element) || $element === '') {
                throw new InvalidArgumentException('Process command elements must not be empty.');
            }
        }
        if ($timeoutSeconds < 1) {
            throw new InvalidArgumentException('Process timeout must be at least 1 second.');
        }
        if ($maxStdoutBytes < 0 || $maxStderrBytes < 0) {
            throw new InvalidArgumentException('Process output byte limits must not be negative.');
        }
        if ($terminateGraceSeconds < 0) {
            throw new InvalidArgumentException('Process terminate grace seconds must not be negative.');
        }
        if ($cwd !== null && !is_dir($cwd)) {
            throw new InvalidArgumentException('Process working directory must exist.');
        }
    }

    /**
     * @param resource|null $pipe
     */
    private function appendOutput(string $output, mixed $pipe, int $maxBytes): string
    {
        if (!is_resource($pipe) || $maxBytes === 0) {
            if (is_resource($pipe)) {
                stream_get_contents($pipe);
            }
            return $output;
        }

        $chunk = stream_get_contents($pipe);
        if ($chunk === false || $chunk === '') {
            return $output;
        }

        $remainingBytes = $maxBytes - strlen($output);
        if ($remainingBytes <= 0) {
            return $output;
        }

        return $output . substr($chunk, 0, $remainingBytes);
    }

    /**
     * @param array<int, resource> $pipes
     */
    private function closePipes(array $pipes): void
    {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }

    /**
     * @param resource $process
     */
    private function terminate(mixed $process, int $graceSeconds): void
    {
        proc_terminate($process, $this->signal(name: 'SIGTERM', fallback: self::SIGTERM_FALLBACK));
        if ($this->waitUntilStopped(process: $process, maxSeconds: $graceSeconds)) {
            return;
        }

        proc_terminate($process, $this->signal(name: 'SIGKILL', fallback: self::SIGKILL_FALLBACK));
        $this->waitUntilStopped(process: $process, maxSeconds: self::KILL_GRACE_SECONDS);
    }

    /**
     * @param resource $process
     */
    private function waitUntilStopped(mixed $process, int $maxSeconds): bool
    {
        $deadline = microtime(true) + $maxSeconds;
        do {
            $status = proc_get_status($process);
            if ($status['running'] !== true) {
                return true;
            }

            usleep(self::POLL_INTERVAL_MICROSECONDS);
        } while (microtime(true) < $deadline);

        return false;
    }

    private function signal(string $name, int $fallback): int
    {
        $value = defined($name) ? constant($name) : $fallback;

        return is_int($value) ? $value : $fallback;
    }
}
