# Framework

This Framework is a small PHP execution foundation for applications that keep their own composition and business requirements outside the framework.

It is designed for projects that want explicit application code, readable boundaries, and a small amount of fixed structure.

## What It Provides

- HTTP `Request` and `Response` primitives
- Routing
- Middleware pipeline
- PSR-11 compatible DI container
- Template renderer interface
- Application bootstrap
- Runtime selection for classic PHP, FrankenPHP worker, and RoadRunner
- Small support classes needed by the runtime

The Framework gives an application a way to start, route, compose, and return responses. It does not decide what the application is.

## Fixed Application Shape

Framework users are expected to use this root shape:

```text
apps/
  kernel/
  shared/
  {app}/
```

`apps/kernel` and `apps/shared` are fixed names.

- `apps/kernel`: application composition, environment-specific wiring, and project-level technology choices
- `apps/shared`: application-side cross-cutting code shared by multiple apps
- `apps/{app}`: each HTTP application, such as `front`, `cms`, `api`, or `preview`

The Framework does not depend on Kernel or Shared. They are the standard project-side layers generated for Framework users.

## What It Does Not Own

The Framework should not own application requirements.

Examples that belong outside the Framework:

- database engine selection
- PDO connection settings
- security headers
- authentication and authorization rules
- audit logging policy
- CORS policy
- cache storage policy
- mail delivery settings
- template engine selection
- validation messages for a specific application
- CMS, blog, media, admin, or API domain rules

Those choices belong in Kernel, Shared, or each App.

## Scaffold

The Framework provides fixed scaffold commands.

```bash
php vendor/bin/scaffold-kernel
php vendor/bin/scaffold-shared
php vendor/bin/scaffold-app front /
```

The generated Kernel and Shared paths are fixed:

```text
apps/kernel  App\Kernel
apps/shared  App\Shared
```

Namespace customization is intentionally not supported. The Framework gives users a clear starting structure instead of making the first decision ambiguous.

`scaffold-app` generates a new app under `apps/{name}`. The default template is intentionally minimal.

## Composer Package Direction

When this Framework is split into a Composer package, the package should include the scaffold commands.

The expected user flow is:

```bash
composer require vendor/framework
php vendor/bin/scaffold-kernel
php vendor/bin/scaffold-shared
php vendor/bin/scaffold-app front /
```

The default shared scaffold generates a Latte implementation for `TemplateRenderer`, so the package should require `latte/latte`. Framework core still depends only on the renderer interface and does not reference Latte classes.

If automatic generation during `composer require` is needed, the package can provide a Composer plugin that calls the same scaffold logic during install events. Without a plugin, Composer does not automatically run dependency package scripts in the root project.

## Examples

The current `webmedia` project contains real applications:

- `apps/front`: public article site
- `apps/cms`: admin CMS

They are useful as a production-shaped example, but they are larger than a first-read sample.

Smaller example templates are planned for the future.

`todo` should be the smallest complete sample. It should make the application shape easy to read with:

- routes
- controller
- interactor
- repository interface
- PDO repository implementation
- a small amount of seed data

`blog` should be a compact but more realistic sample. It should show article list/detail flows without the full CMS feature set.

The purpose of these templates is not to replace the CMS. It is to make the Framework's application shape understandable in a few minutes.

## Reading Order

For a first read, start here:

1. `src/Framework/README.md`
2. `src/Framework/Bootstrap/README.md`
3. `src/Framework/Container/README.md`
4. `src/Framework/Http/Middleware/README.md`
5. `bin/README.md`
6. `apps/kernel/README.md`
7. `apps/shared/README.md`

The top-level `README.md` describes the `webmedia` project itself. This README describes the Framework.
