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

`PlaceholderLogger` is kept in the Framework because missing logging should fail explicitly. If an application wants to discard logs, it should bind `Psr\Log\LoggerInterface` to an intentional null logger. Silent absence is not the default path.

`Session` and flash values are kept in the Framework because session state and post-redirect-get flash messages are basic web primitives. Application code still owns the flash key names, messages, and display policy.

`ProcessRunner` is kept in the Framework because `proc_open()` is a low-level PHP runtime boundary that is easy to use unsafely. The Framework only provides a synchronous, shell-free, timeout-bound runner with bounded stdout/stderr capture and startup warning reporting. Applications still decide which executable is allowed and what a failed process means for their use case. See `src/Framework/Process/README.md` for usage.

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

The default Shared scaffold also provides small PDO helpers for the default persistence examples. Framework core does not require PDO.

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

`scaffold-app` generates a new app under `apps/{name}`. The default `minimal` template is a single-controller HTML starter with a `GET <path-prefix>` route, a Latte template, and a `TemplateRenderer` binding. Use `--template=bare` for config only, `--template=todo` for a small PDO-backed Todo starter, and `--template=layered` for a larger Application / Domain / Infrastructure starter.

## Composer Package Direction

When this Framework is split into a Composer package, the package should include the scaffold commands. Framework installation should create Kernel and Shared during project setup. `scaffold-app` is for adding individual apps after that setup. Templates do not decide whether Kernel or Shared exist.

The default shared scaffold generates a Latte implementation for `TemplateRenderer`, so the package should require `latte/latte`. Framework core still depends only on the renderer interface and does not reference Latte classes.

Automatic generation during `composer require` requires a Composer plugin. This repository includes `src/Framework/Installer/Composer/FrameworkInstallerPlugin.php` for that package direction. It is Framework installer code, but not Framework runtime code. The plugin creates Kernel and Shared only when their required config files are missing.

## Examples

The current `webmedia` project contains real applications:

- `apps/front`: public article site
- `apps/cms`: admin CMS

They are useful as a production-shaped example, but they are larger than a first-read sample.

A smaller `todo` template is available for a first PDO-backed app. A small `blog` template is planned for the future. Their purpose is to make the Framework's application shape understandable in a few minutes.

## Reading Order

For a first read, start here:

1. `src/Framework/README.md`
2. `src/Framework/Bootstrap/README.md`
3. `src/Framework/Container/README.md`
4. `src/Framework/Http/Middleware/README.md`
5. `src/Framework/Process/README.md`
6. `bin/README.md`
7. `apps/kernel/README.md`
8. `apps/shared/README.md`

The top-level `README.md` describes the `webmedia` project itself. This README describes the Framework.
