# Process

`ProcessRunner` runs an external process from PHP without asking application code to handle `proc_open()` directly.

It is a small runtime primitive for synchronous commands. It does not decide which executable is allowed, what arguments are valid for a domain, how failures are shown to users, or whether a job should be retried.

## Basic Usage

Pass the command as a list of arguments.

```php
use Pera\Framework\Process\ProcessRunner;

$result = $processRunner->run(
    command: [
        'vipsthumbnail',
        $sourcePath,
        '--size',
        'down',
        '-s',
        '480x480',
        '-o',
        $destinationPath . '[Q=78,strip]',
    ],
    timeoutSeconds: 20,
    maxStdoutBytes: 0,
    maxStderrBytes: 0,
);

if (!$result->successful()) {
    // The application decides what this failure means.
}
```

The command must be an array. Do not pass a shell command string.

```php
// Good: shell is not invoked.
$runner->run(command: ['convert', $source, $destination], timeoutSeconds: 10);

// Bad: application code must not build shell command strings.
$runner->run(command: ['convert ' . escapeshellarg($source) . ' ' . escapeshellarg($destination)], timeoutSeconds: 10);
```

## Result

`ProcessResult::successful()` is true only when all of these are true:

- the process started
- the runner did not time out
- the process exit code is `0`

Inspect the result when the application needs a more specific decision.

```php
if ($result->failedToStart()) {
    $message = $result->startupError;
}

if ($result->timedOut) {
    // The timeout was reached and cleanup was attempted.
}

if ($result->exitCode !== 0) {
    // The process started but reported failure.
}
```

`exitCode === null` means no normal exit code was produced. This can happen when startup failed, timeout cleanup took over, or PHP could not provide a usable exit code.

## Output Limits

Stdout and stderr are captured with independent byte limits.

```php
$result = $runner->run(
    command: ['php', '-v'],
    timeoutSeconds: 5,
    maxStdoutBytes: 4096,
    maxStderrBytes: 4096,
);
```

Use `0` when the caller only needs success or failure and does not need output. The runner still drains the pipes so the child process does not block on full stdout or stderr.

## Responsibility Boundary

The Framework owns the dangerous PHP runtime mechanics:

- call `proc_open()` with an array command
- avoid shell command strings
- capture startup warnings without `@` suppression
- drain stdout and stderr
- close process pipes
- enforce a timeout
- attempt terminate / kill / close cleanup on timeout
- return a structured result

The application owns the use-case policy:

- choose the executable from configuration or an allowlist
- validate arguments before building the command
- decide whether a non-zero exit code is acceptable
- decide what to store in the database
- decide how to log, audit, retry, queue, or display failures

Do not put domain policy into `ProcessRunner`.

## Worker Mode

`ProcessRunner` is safe to reuse in FrankenPHP worker mode because it keeps process handles, pipes, output buffers, and timeout state inside a single `run()` call.

It does not provide background jobs or long-running child process supervision. A timed-out process is cleaned up on a best-effort basis. If a command starts its own descendant processes or daemonizes, the runner cannot guarantee that every descendant was terminated.

For async work, queueing, retries, and manual re-run workflows, build an application-side job or media workflow on top of `ProcessRunner`.

## Do Not

- do not pass user-controlled executable names directly
- do not build a shell string and pass it as one argument
- do not use it for background daemons
- do not assume timeout cleanup kills every descendant process
- do not treat stderr text as safe HTML
- do not hide application failure policy inside Framework code

## 日本語

`ProcessRunner` は、PHP から外部プロセスを実行する時に、アプリケーションコードが `proc_open()` を直接扱わなくて済むようにするための部品です。

これは同期コマンド用の小さな runtime primitive です。どの executable を許可するか、どの引数が業務上正しいか、失敗をユーザーへどう見せるか、retry するかどうかは決めません。

command は shell 文字列ではなく、引数の list として渡します。

```php
$result = $processRunner->run(
    command: ['vipsthumbnail', $sourcePath, '--size', 'down', '-s', '480x480', '-o', $destinationPath . '[Q=78,strip]'],
    timeoutSeconds: 20,
    maxStdoutBytes: 0,
    maxStderrBytes: 0,
);
```

`successful()` が true になるのは、プロセスが起動し、timeout せず、exit code が `0` の場合だけです。起動失敗は `startupError`、timeout は `timedOut`、終了コードは `exitCode` を見ます。

Framework が担当するのは、shell を経由しない実行、`@` を使わない起動 warning 捕捉、pipe の drain と close、timeout、timeout 時の best-effort cleanup、構造化された結果の返却です。

Application が担当するのは、許可する executable、引数の validation、exit code の意味、DB への保存、log / audit / retry / queue / 画面表示です。

FrankenPHP worker mode でも、process handle、pipe、出力 buffer、timeout 状態は 1 回の `run()` の中に閉じます。ただし、background job や長時間動く子プロセスの監督機能ではありません。timeout 時の cleanup は best effort であり、外部コマンドがさらに起動した子孫プロセスまで完全に終了したことは保証しません。
