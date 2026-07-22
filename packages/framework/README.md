# pera Framework

`pera` is a small PHP execution foundation for applications that keep their own composition and business requirements outside the framework.

It is designed for projects that want explicit application code, readable boundaries, and a small amount of fixed structure. The core stays small because it owns only the web runtime rules that should be reliable before application code runs.

The name of this Framework is `pera`.

## What It Provides

- HTTP `Request` and `Response` primitives
- Routing
- Middleware pipeline
- PSR-11 compatible DI container
- Template renderer interface
- Application bootstrap
- Runtime selection for classic PHP and FrankenPHP worker
- Safe synchronous external process execution
- Small support classes needed by the runtime

`pera` gives an application a way to start, route, compose, and return responses. It does not decide what the application is.

## Required Web Rules

Small does not mean unopinionated.

`pera` keeps rules that are part of the baseline shape of a PHP web application.

For example, malformed global request input is represented by a Framework HTTP exception before it reaches application code. The Kernel entrypoint converts that failure into a bad request response. This keeps the Framework strict at the boundary without making every application controller defend against invalid runtime input.

`Csrf` and `CsrfMiddleware` are kept in the Framework because CSRF protection is a normal safety rule for session-backed web forms. The session key name is also fixed by the Framework. Users should not have to choose this rule before they can build a safe form. Applications decide whether to apply the middleware and which paths to exclude.

`PlaceholderLogger` is kept in the Framework because missing logging should fail explicitly. The default Shared scaffold binds `Psr\Log\LoggerInterface` to it as a temporary starting point, and applications replace that binding with their real PSR-3 logger. If an application wants to discard logs, it should bind `Psr\Log\LoggerInterface` to an intentional null logger. Silent absence is not the default path.

`Session` and flash values are kept in the Framework because session state and post-redirect-get flash messages are basic web primitives. Application code still owns the flash key names, messages, and display policy. A request-scoped native `Session` starts PHP session storage only on first state access. Constructing `Session` or passing a safe request through `CsrfMiddleware` does not make an otherwise stateless response stateful. See `src/Security/README.md` for the smallest Controller example, application-owned Session state, CSRF, testing, and worker lifecycle.

`ProcessRunner` is kept in the Framework because `proc_open()` is a low-level PHP runtime boundary that is easy to use unsafely. The Framework only provides a synchronous, shell-free, timeout-bound runner with bounded stdout/stderr capture and startup warning reporting. Applications still decide which executable is allowed and what a failed process means for their use case. See `src/Process/README.md` for usage.

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

`pera` should not own application requirements.

Examples that belong outside the Framework:

- database engine selection
- PDO connection settings
- PDO persistence helpers
- security headers
- authentication and authorization rules
- audit logging policy
- CORS policy
- cache storage policy
- mail delivery settings
- environment file loading
- template engine selection
- input normalization, such as trimming request values
- validation library selection
- validation messages for a specific application
- external command allowlists and process business policy
- CMS, blog, media, admin, or API domain rules

Those choices belong in Kernel, Shared, or each App.

The default Shared scaffold provides `App\Shared\Validation` as a small form validation starting point, and the layered app scaffold shows how to use it. Framework core does not own validation classes.

PDO-backed app templates own their connection factory and persistence code. Neither Framework, the Kernel scaffold, nor the Shared scaffold provides a project-wide PDO binding.

## Installation

After the package is published, install the Framework library into an existing project with Composer:

```bash
composer require pera/framework
```

During local package development, point the consuming project's `composer.json` at the package directory and require its development version:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../path/to/pera/packages/framework",
      "options": {
        "symlink": true,
        "versions": {
          "pera/framework": "dev-main"
        }
      }
    }
  ],
  "require": {
    "pera/framework": "dev-main"
  }
}
```

Then run `composer update pera/framework --with-all-dependencies`. The install adds only the Framework library and its explicit vendor binaries. It does not create `apps/kernel`, `apps/shared`, an application, or a public entrypoint.

## Scaffold

`pera` provides fixed scaffold commands.

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

Namespace customization is intentionally not supported. `pera` gives users a clear starting structure instead of making the first decision ambiguous.

`scaffold-app` generates a new app under `apps/{name}`. The default `minimal` template is a single-controller HTML starter with a `GET <path-prefix>` route, a Latte template, and a `ITemplateRenderer` binding. Use `--template=bare` for config only, `--template=todo` for a small PDO-backed Todo starter, and `--template=layered` for a larger Application / Domain / Infrastructure starter.

Scaffold dependencies follow generated capabilities. Kernel adds Dotenv, Shared and HTML app templates add Latte, and PDO-backed app templates add the PDO and SQLite platform requirements to the consuming root project. `bare` leaves the manifest untouched. The scaffold updates only `composer.json`; it prints the explicit Composer command for updating the lock file and installed packages instead of starting a nested Composer process.

Generated source is stored as versioned files under `templates/`. The binary scripts own orchestration and atomic publication, not copies of PHP and Latte source embedded in command code. See `bin/README.md` for the template and ownership contract.

## Composer Package

`pera/framework` contains Framework code and the explicit scaffold commands. Installing the Framework library alone does not create Kernel or Shared automatically. A user who wants the standard project shape runs the scaffold commands explicitly; a new-project skeleton may run the same setup as part of project creation.

Generated Kernel code belongs entirely to the consuming project. It remains under `App\Kernel`, is optional, and is never overwritten or upgraded by a Framework package update.

Framework runtime dependencies and starter dependencies are separate. FastRoute and the PSR interfaces used by Framework belong to the Framework package. Dotenv, Latte, database drivers, migrations, and other implementations referenced by generated project code belong to the consuming root project or new-project skeleton. The Framework package must not require Latte merely because the standard Shared scaffold offers a Latte adapter.

There is no Composer installer plugin in this package. If automatic generation is required in the future, it must be released and tested as a separate Composer plugin package. The default path remains explicit `vendor/bin` commands.

Public API and compatibility rules are documented in [`API.md`](API.md). Release changes are recorded in [`CHANGELOG.md`](CHANGELOG.md), and private vulnerability reporting expectations are documented in [`SECURITY.md`](SECURITY.md).

## Monorepo Development

The `webmedia` root consumes this package through a Composer path repository with `symlink: true`. Composer therefore links `vendor/pera/framework` to `packages/framework` instead of copying the package.

- Changes to existing Framework PHP files, README files, and scaffold script contents are visible immediately. Running `composer install` again is unnecessary.
- Changes to this package's `require`, PSR-4 mapping, or `bin` list require `composer update pera/framework --with-all-dependencies` from the root so that `composer.lock`, autoload metadata, and vendor binary proxies are regenerated.
- A fresh checkout should use the normal `composer install` from the root lock file.
- Consumers of a published non-path package receive Framework changes through the normal `composer update pera/framework` flow.

## Examples

The current `webmedia` project contains real applications:

- `apps/front`: public article site
- `apps/cms`: admin CMS

They are useful as a production-shaped example, but they are larger than a first-read sample.

A smaller `todo` template is available for a first PDO-backed app. A small `blog` template is planned for the future. Their purpose is to make the Framework's application shape understandable in a few minutes.

## Reading Order

For a first read, start here:

1. `README.md`
2. `src/Bootstrap/README.md`
3. `src/Container/README.md`
4. `src/Http/Middleware/README.md`
5. `src/Security/README.md`
6. `src/Process/README.md`
7. `bin/README.md`
Generated Kernel and Shared directories contain their own project-side README files. This package README describes only the Framework and its scaffold commands.
