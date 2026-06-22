<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Security\Csrf;
use App\Framework\Security\CsrfMiddleware;
use App\Framework\Security\Session;
use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\ApplicationLog\Application\ApplicationLoggerInterface;
use App\Shared\ApplicationLog\Infrastructure\JsonFileApplicationLogger;
use App\Shared\Audit\Infrastructure\JsonFileAuditLogger;
use App\Shared\Cache\Infrastructure\FileSimpleCache;
use App\Shared\Cache\Infrastructure\NullCache;
use App\Shared\Markdown\Infrastructure\Markdown;
use App\Shared\SecurityLog\Application\SecurityLoggerInterface;
use App\Shared\SecurityLog\Infrastructure\JsonFileSecurityLogger;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

return static function (Container $container, string $root): void {
    $envString = static function (string $key, string $default = ''): string {
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $value = getenv($key);
            if ($value === false) {
                return $default;
            }
        }

        return is_scalar($value) ? (string) $value : $default;
    };

    $csv = static function (string $value): array {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value),
        ), static fn (string $item): bool => $item !== ''));
    };

    $container->singleton(id: Csrf::class, factory: function (Container $container): Csrf {
        $session = $container->get(id: Session::class);
        if (!$session instanceof Session) {
            throw new \RuntimeException('Session service is not registered.');
        }

        return new Csrf(session: $session);
    });
    $container->singleton(id: CsrfMiddleware::class, factory: function (Container $container) use ($csv, $envString): CsrfMiddleware {
        $csrf = $container->get(id: Csrf::class);
        if (!$csrf instanceof Csrf) {
            throw new \RuntimeException('CSRF service is not registered.');
        }

        $appEnv = strtolower($envString('APP_ENV', 'production'));

        return new CsrfMiddleware(
            csrf: $csrf,
            excludedPaths: $csv($envString('CSRF_EXCLUDED_PATHS')),
            enabled: !in_array($appEnv, ['stg', 'staging', 'dev', 'develop', 'development', 'test'], true),
        );
    });
    $container->singleton(id: Markdown::class, factory: fn (): Markdown => new Markdown());
    $container->singleton(id: ApplicationLoggerInterface::class, factory: fn (): ApplicationLoggerInterface => new JsonFileApplicationLogger($root . '/storage/logs/application.log'));
    $container->singleton(id: LoggerInterface::class, factory: function (Container $container): LoggerInterface {
        $logger = $container->get(id: ApplicationLoggerInterface::class);
        if (!$logger instanceof LoggerInterface) {
            throw new \RuntimeException('Application logger service is not registered.');
        }

        return $logger;
    });
    $container->singleton(id: AuditLoggerInterface::class, factory: fn (): AuditLoggerInterface => new JsonFileAuditLogger($root . '/storage/logs/audit.log'));
    $container->singleton(id: SecurityLoggerInterface::class, factory: fn (): SecurityLoggerInterface => new JsonFileSecurityLogger($root . '/storage/logs/security.log'));
    $container->singleton(id: CacheInterface::class, factory: static function () use ($envString, $root): CacheInterface {
        $driver = $envString('SHARED_CACHE_DRIVER', 'file');
        $driver = strtolower($driver);

        return match ($driver) {
            'null', 'none', 'off' => new NullCache(),
            default => new FileSimpleCache(directory: $root . '/storage/cache/shared'),
        };
    });
};
