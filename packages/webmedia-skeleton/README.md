# Pera Webmedia Skeleton

This proprietary project skeleton provides a production-oriented WEB media and CMS built on `pera/framework`.

## Development

```bash
cp .env.example .env
docker compose build app
docker compose run --rm app composer install
docker compose up -d db
docker compose run --rm app composer migrate
docker compose run --rm app composer seed
docker compose up -d app
```

The Front application is available at <http://localhost:8080/> and the CMS login is available at <http://localhost:8080/admin/login>.

The development seed creates `admin@example.com` with password `password`. Replace or remove development credentials before deployment. Do not run development seeds in production.

## Verification

```bash
docker compose run --rm app composer validate --strict
docker compose run --rm app composer test
docker compose run --rm app composer analyse
docker compose run --rm app composer check-platform-reqs
```

The generated Kernel, Shared, Front, and CMS source is owned and maintained by the consuming project. Framework runtime updates remain available through Composer.

## License

This package is proprietary. Use and redistribution require a separate commercial license.
