<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\Audit\Infrastructure\PlaceholderAuditLogger;
use App\Shared\Cache\Infrastructure\FileSimpleCache;
use App\Shared\Cache\Infrastructure\NullCache;
use App\Shared\Logging\Infrastructure\JsonFileLogger;
use App\Shared\Markdown\Infrastructure\Markdown;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

return static function (Container $container, string $root): void {
    $container->singleton(id: \PDO::class, factory: fn (): \PDO => (new ConnectionFactory())->create());
    $container->singleton(id: Csrf::class, factory: function (Container $container): Csrf {
        $session = $container->get(id: Session::class);
        if (!$session instanceof Session) {
            throw new \RuntimeException('Session service is not registered.');
        }

        return new Csrf(session: $session);
    });
    $container->singleton(id: Markdown::class, factory: fn (): Markdown => new Markdown());
    $container->singleton(id: LoggerInterface::class, factory: fn (): LoggerInterface => new JsonFileLogger($root . '/storage/logs/app.log'));
    $container->singleton(id: AuditLoggerInterface::class, factory: fn (): AuditLoggerInterface => new PlaceholderAuditLogger());
    $container->singleton(id: CacheInterface::class, factory: static function () use ($root): CacheInterface {
        $driver = $_ENV['SHARED_CACHE_DRIVER'] ?? 'file';
        $driver = is_scalar($driver) ? strtolower((string) $driver) : 'file';

        return match ($driver) {
            'null', 'none', 'off' => new NullCache(),
            default => new FileSimpleCache(directory: $root . '/storage/cache/shared'),
        };
    });
};
