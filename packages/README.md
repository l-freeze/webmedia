# Package development

`packages/` contains Composer packages developed inside the private `webmedia` monorepo.

- `packages/framework`: `pera/framework` library
- `packages/skeleton`: `pera/skeleton` project template

This document describes local package verification before the packages are published. These repository declarations are test-only and must not be added to either package's distributed `composer.json`.

## Verify `pera/skeleton` with local path repositories

Run these commands from the `webmedia` repository root. Use a new target directory name when repeating the test because Composer intentionally refuses to overwrite an existing project.

```bash
mkdir -p /home/yamamoto/pera-packaging-test
```

First, mirror the Skeleton into a new project without resolving its dependencies yet:

```bash
docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app composer create-project \
  --repository='{"type":"path","url":"/app/packages/skeleton","options":{"symlink":false,"versions":{"pera/skeleton":"0.1.0"}}}' \
  --no-install \
  --no-interaction \
  --no-progress \
  pera/skeleton \
  /pera-packaging-test/my-app \
  0.1.0
```

The Skeleton requires `pera/framework:^0.1`. Add the unpublished Framework as a path repository to the generated test project:

```bash
docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app composer config \
  --working-dir=/pera-packaging-test/my-app \
  repositories.framework \
  '{"type":"path","url":"/app/packages/framework","options":{"symlink":false,"versions":{"pera/framework":"0.1.0"}}}'
```

Install the dependencies and generate the test project's `composer.lock`:

```bash
docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app composer install \
  --working-dir=/pera-packaging-test/my-app \
  --no-interaction \
  --no-progress \
  --prefer-dist
```

`symlink:false` is intentional. It verifies copied package contents instead of allowing missing package files to be hidden by a symlink back to the monorepo.

## Validate the generated project

Run the Skeleton test suite, PHPStan, and Composer validation through the monorepo container. The generated test manifest contains `/app/packages/framework`, so Composer dependency operations must use this container while the Framework is unpublished.

```bash
docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app composer --working-dir=/pera-packaging-test/my-app test

docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app composer --working-dir=/pera-packaging-test/my-app analyse

docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app composer --working-dir=/pera-packaging-test/my-app validate --strict
```

## Verify app scaffolds from clean projects

Do not validate several scaffold changes in one already-modified project. For each of `bare`, `minimal`, `todo`, and `layered`, repeat the path-repository `create-project`, Framework repository configuration, and `composer install` steps above with a new target such as:

```text
/home/yamamoto/pera-packaging-test/scaffold-bare
/home/yamamoto/pera-packaging-test/scaffold-minimal
/home/yamamoto/pera-packaging-test/scaffold-todo
/home/yamamoto/pera-packaging-test/scaffold-layered
```

Then run exactly one scaffold in each fresh project through its installed vendor binary:

```bash
docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app php /pera-packaging-test/scaffold-bare/vendor/bin/scaffold-app bare /bare --template=bare

docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app php /pera-packaging-test/scaffold-minimal/vendor/bin/scaffold-app site /site --template=minimal

docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app php /pera-packaging-test/scaffold-todo/vendor/bin/scaffold-app todo /todo --template=todo

docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app php /pera-packaging-test/scaffold-layered/vendor/bin/scaffold-app admin /admin --template=layered
```

Run the Composer command printed by each scaffold from that same project, followed by `composer validate --strict`, `composer test`, and `composer analyse`. Confirm that the new PSR-4 entry and `ApplicationDefinition` were added automatically and test the generated route through the real Kernel. The root integration test `SkeletonCreateProjectTest` automates this isolation: each template gets a separately copied Framework package, a separately copied Skeleton package, a fresh Composer install with `symlink:false`, one vendor-binary scaffold invocation, and Kernel-level HTTP verification.

Start the generated project's own FrankenPHP environment separately:

```bash
cd /home/yamamoto/pera-packaging-test/my-app
docker compose up --build
```

Confirm the following at <http://localhost:8080/>:

- HTTP 200 is returned repeatedly in worker mode.
- The Minimal Latte page is rendered.
- Security headers are present.
- The stateless initial page does not send a `Set-Cookie` header.

If Docker-created files are not owned by the host user, restore ownership from the monorepo root:

```bash
docker compose run --rm \
  -v /home/yamamoto/pera-packaging-test:/pera-packaging-test \
  app chown -R "$(id -u):$(id -g)" /pera-packaging-test/my-app
```

## After publication

Once `pera/framework` and `pera/skeleton` have compatible public tags, the test-only repository setup is unnecessary:

```bash
composer create-project pera/skeleton my-app
```
