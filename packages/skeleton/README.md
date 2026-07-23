# Pera project skeleton

`pera/skeleton` creates a ready-to-run Pera project with a consumer-owned Kernel, Shared application support, and a small Latte page mounted at `/`.

## Create a project

```bash
composer create-project pera/skeleton my-app
cd my-app
docker compose up --build
```

Open <http://localhost:8080/>. Set `APP_PORT` when port 8080 is already in use.

The generated project owns all files under `apps/`. Updating `pera/framework` never overwrites Kernel, Shared, or application code.

## Included structure

- `apps/kernel`: application composition and request dispatch
- `apps/shared`: shared security middleware, validation, logging binding, and Latte adapter
- `apps/minimal`: a stateless single-controller HTML application
- `public/index.php`: classic and FrankenPHP worker entrypoint
- `compose.yaml` and `.docker`: a database-free FrankenPHP development environment

The initial project requires Dotenv and Latte because its generated Kernel and Shared code use them. It does not require PDO, a database driver, or a migration tool.

## Add another application

All Framework scaffold commands are installed under `vendor/bin`:

```bash
docker compose run --rm app php vendor/bin/scaffold-app api /api --template=bare
docker compose run --rm app php vendor/bin/scaffold-app site /site --template=minimal
docker compose run --rm app php vendor/bin/scaffold-app todo /todo --template=todo
docker compose run --rm app php vendor/bin/scaffold-app admin /admin --template=layered
```

The command prints the `ApplicationDefinition` and PSR-4 entry that must be added to Kernel and `composer.json`. It also updates runtime dependency declarations required by the selected template. Run the Composer command printed by the scaffold after reviewing the manifest change.

When PHP 8.5 is installed locally, the same commands can be run directly as `php vendor/bin/scaffold-app ...`.

## Environment

Kernel loads `.env` when the file exists. No environment file is required for the initial page. To customize session cookies:

```bash
cp .env.example .env
```

Native PHP session storage starts lazily when application code first accesses `Pera\Framework\Security\Session`; the initial page remains stateless.

Shared middleware enables security headers and CSRF protection by default. Safe requests such as the initial `GET /` do not start Session storage. If the project later adds a stateless API, comment out or remove `CsrfMiddleware::class` from `apps/shared/config/middleware.php` and add it explicitly to each browser/session app's `config/middleware.php`. The Todo scaffold renders the required `_csrf` form fields.

## Runtime

Docker Compose uses FrankenPHP worker mode. The default `dev` worker preset watches application source and templates. To disable worker watching:

```bash
CADDY_WORKER_CONFIG=production docker compose up --build --force-recreate
```

The preset only changes worker file watching; it is not a complete production deployment configuration.

Without Docker, use any PHP 8.5 environment satisfying the Composer platform requirements. Classic mode is the default when `APP_RUNTIME` is not set:

```bash
composer install
php -S 127.0.0.1:8080 -t public
```

## Quality checks

```bash
docker compose run --rm app composer test
docker compose run --rm app composer analyse
docker compose run --rm app composer validate --strict
```

## License

Pera project skeleton is released under the MIT License.
