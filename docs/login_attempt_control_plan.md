# ログイン試行制御の実装方針

## 目的

CMS ログインに対する短時間の連続失敗を抑制する。

IP 制限は行わない。恒久的な遮断ではなく、一定時間内の失敗回数に応じてログイン試行を一時的に止める。

画面に出す文言は、通常失敗と制限時で同じにする。

```text
ログインに失敗しました。時間をおいて再度お試しください。
```

## 基本判断

`RateLimit` という汎用 Application は作らない。

Rate limit は守る対象によって意味が変わる。ログイン、問い合わせ送信、記事作成、メディアアップロードでは、識別子、記録する結果、解除条件、ログ粒度が違う。

そのため、今回の制御は `Auth/Login` UseCase に付く補助部品として扱う。

UseCase はあくまで `Login` であり、`LoginAttempt` は独立 UseCase ではない。

## 配置

```text
apps/cms/src/Application/Auth/Login/
  Input.php
  Interactor.php
  Output.php
  Port/
    PasswordVerifier.php
  Repository/
    ILoginAttemptRepository.php
  Value/
    LoginAttemptResult.php

apps/cms/src/Infrastructure/Persistence/
  PdoLoginAttemptRepository.php
```

`Login/Input.php`、`Login/Interactor.php`、`Login/Output.php` の UseCase 形は維持する。

ログイン試行制御は `Login` UseCase の一部として `Interactor` が進行する。

`LoginAttempts` のような協力オブジェクトを UseCase 直下に置かない。

`LoginAttemptPolicy` も初期実装では作らない。閾値判断は `Login\Interactor` の private method に閉じ込める。

`PasswordVerifier` は PHP の password API への Application port なので `Login/Port/` に置く。

ログイン試行制御そのものは port にしない。ログイン制限の判定条件は Application 側の方針であり、Infrastructure へ押し出さない。

## 役割

```text
Login/Interactor.php
  ログイン UseCase 全体を進行する。
  ログイン試行制御の流れと閾値判断もここで扱う。
  補助 private method へ分け、public な補助 class は増やさない。

Login/Port/PasswordVerifier.php
  入力パスワードが保存済み hash と一致するか検証する port。

Login/Value/LoginAttemptResult.php
  記録する結果の enum。

Login/Repository/ILoginAttemptRepository.php
  最近の失敗回数の取得と、試行結果の保存を行う Application port。
```

## 呼び出し

Controller は `Login\Interactor` だけを呼ぶ。

ログイン試行制御の補助部品は Controller に露出させない。

```text
LoginController
  -> Login\Interactor
       -> ILoginAttemptRepository
       -> IUserRepository
       -> PasswordVerifier
       -> ILoginAttemptRepository
  -> SecurityLog
  -> Response
```

## Login Input

IP address が判定に必要になるため、`Login\Input` に追加する。

```php
final readonly class Input
{
    public function __construct(
        public string $email,
        public string $password,
        public string $ipAddress,
    ) {
    }
}
```

Controller 側では `REMOTE_ADDR` を渡す。

```php
$output = $this->login->execute(input: new Input(
    email: $request->input(key: 'email'),
    password: $request->input(key: 'password'),
    ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR') ?? '',
));
```

## Login Output

制限時だけ SecurityLog の結果を変えられるよう、`blocked` を持たせる。

```php
final readonly class Output
{
    private function __construct(
        public bool $authenticated,
        public ?int $userId,
        public ?string $role,
        public bool $blocked,
    ) {
    }

    public static function failed(): self
    {
        return new self(false, null, null, false);
    }

    public static function blocked(): self
    {
        return new self(false, null, null, true);
    }

    public static function authenticated(int $userId, string $role): self
    {
        return new self(true, $userId, $role, false);
    }
}
```

画面文言は `failed` と `blocked` で同じにする。

## Interactor の流れ

```php
final readonly class Interactor
{
    private const ATTEMPT_WINDOW_SECONDS = 600;
    private const MAX_FAILURES_PER_EMAIL_AND_IP = 5;
    private const MAX_FAILURES_PER_IP = 20;

    public function __construct(
        private IUserRepository $users,
        private PasswordVerifier $passwordVerifier,
        private ILoginAttemptRepository $loginAttempts,
    ) {
    }

    public function execute(Input $input): Output
    {
        $now = new DateTimeImmutable();
        $since = $this->loginAttemptWindowStart(now: $now);
        $emailIpFailures = $this->loginAttempts->countFailuresByEmailAndIp(
            email: $input->email,
            ipAddress: $input->ipAddress,
            since: $since,
        );
        $ipFailures = $this->loginAttempts->countFailuresByIp(
            ipAddress: $input->ipAddress,
            since: $since,
        );

        if ($this->tooManyLoginAttempts(
            emailIpFailures: $emailIpFailures,
            ipFailures: $ipFailures,
        )) {
            $this->loginAttempts->record(
                email: $input->email,
                ipAddress: $input->ipAddress,
                result: LoginAttemptResult::Blocked,
                attemptedAt: $now,
            );

            return Output::blocked();
        }

        $user = $this->users->findByEmail(email: $input->email);

        if ($user === null || !$this->passwordVerifier->verify(
            plainPassword: $input->password,
            passwordHash: $user->passwordHash,
        )) {
            $this->loginAttempts->record(
                email: $input->email,
                ipAddress: $input->ipAddress,
                result: LoginAttemptResult::Failure,
                attemptedAt: $now,
            );

            return Output::failed();
        }

        $this->loginAttempts->record(
            email: $input->email,
            ipAddress: $input->ipAddress,
            result: LoginAttemptResult::Success,
            attemptedAt: $now,
        );

        return Output::authenticated(userId: $user->id, role: $user->role);
    }

    private function loginAttemptWindowStart(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify('-' . self::ATTEMPT_WINDOW_SECONDS . ' seconds');
    }

    private function tooManyLoginAttempts(int $emailIpFailures, int $ipFailures): bool
    {
        return $emailIpFailures >= self::MAX_FAILURES_PER_EMAIL_AND_IP
            || $ipFailures >= self::MAX_FAILURES_PER_IP;
    }
}
```

## 判定条件

初期値は `Login\Interactor` の private const とする。

```text
ATTEMPT_WINDOW_SECONDS=600
MAX_FAILURES_PER_EMAIL_AND_IP=5
MAX_FAILURES_PER_IP=20
```

判定は `failure` だけを数える。

```text
email_hash + ip_address:
  10分間に5回 failure で blocked

ip_address:
  10分間に20回 failure で blocked
```

`success` と `blocked` も記録するが、次回判定の失敗回数には含めない。

## LoginAttempts は作らない

`LoginAttempts` という協力オブジェクトは作らない。

UseCase ディレクトリ直下に `Input.php`、`Interactor.php`、`Output.php` 以外の業務補助クラスが増えると、UseCase の形が崩れて見えるためである。

また、`LoginAttempts` を port にするのも避ける。

`canAttempt()` のような method を port にすると、「10分間に何回まで許すか」「email + IP と IP 全体の二段で見るか」「何を failure と数えるか」という Application 固有の方針が Infrastructure 実装へ逃げやすい。

抽象化する境界は、永続化だけにする。

```php
interface ILoginAttemptRepository
{
    public function countFailuresByEmailAndIp(string $email, string $ipAddress, DateTimeImmutable $since): int;

    public function countFailuresByIp(string $ipAddress, DateTimeImmutable $since): int;

    public function record(
        string $email,
        string $ipAddress,
        LoginAttemptResult $result,
        DateTimeImmutable $attemptedAt,
    ): void;
}
```

閾値判断は `Login\Interactor` の private method に置く。

## email の扱い

email は raw で保存しない。

```text
email_hash = HMAC-SHA256(normalized_email, app_secret)
```

正規化は最低限、trim と lowercase とする。

```php
$normalizedEmail = strtolower(trim($email));
```

HMAC key は既存の環境値を使うか、必要なら専用 env を追加する。

## DB

```text
login_attempts
  id
  email_hash
  ip_address
  result
  attempted_at
```

`result` は enum 相当の文字列。

```php
enum LoginAttemptResult: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Blocked = 'blocked';
}
```

必要な index。

```text
login_attempts_email_hash_ip_attempted_at_idx
  email_hash, ip_address, attempted_at

login_attempts_ip_attempted_at_idx
  ip_address, attempted_at
```

## SecurityLog

既存の `cms.login` を使う。

```text
success:
  eventType = cms.login
  result = success

failure:
  eventType = cms.login
  result = failure

blocked:
  eventType = cms.login
  result = blocked
```

画面文言は `failure` と `blocked` で同じにする。

## container

```php
$container->bind(
    abstract: Auth\Login\Repository\ILoginAttemptRepository::class,
    concrete: PdoLoginAttemptRepository::class,
);
```

## テスト

最低限、以下を追加する。

```text
LoginInteractorTest
  制限されていない場合は通常の password verify に進む
  email + IP の失敗数が閾値以上なら blocked
  IP 全体の失敗数が閾値以上なら blocked
  認証失敗時に failure を記録する
  認証成功時に success を記録する
  blocked 時に password verify を実行しない

PdoLoginAttemptRepositoryTest
  window 内の failure だけ数える
  success / blocked は失敗数に含めない
  window 外の failure は数えない
```

Controller test では、`blocked` のときも画面文言が通常失敗と同じであること、SecurityLog result が `blocked` になることを確認する。

## 実装順

1. migration で `login_attempts` を追加する。
2. `Login/Repository/ILoginAttemptRepository.php`、`Login/Value/LoginAttemptResult.php` を追加する。
3. `PdoLoginAttemptRepository` を追加する。
4. `Login\Input` に `ipAddress` を追加する。
5. `Login\Output` に `blocked` を追加する。
6. `Login\Interactor` に `ILoginAttemptRepository` を差し込み、試行制御を private method に閉じ込める。
7. `LoginController` のエラー文言と SecurityLog result を調整する。
8. container binding を追加する。
9. PHPUnit / PHPStan / HTTP 経由のログイン失敗確認を行う。
