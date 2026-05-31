<?php

declare(strict_types=1);

use App\Framework\Container\Container;
use App\Framework\Content\Markdown;
use App\Framework\Database\ConnectionFactory;
use App\Framework\Logging\AuditLoggerInterface;
use App\Framework\Logging\PlaceholderAuditLogger;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Shared\Infrastructure\Logging\JsonFileLogger;
use Psr\Log\LoggerInterface;

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
};
