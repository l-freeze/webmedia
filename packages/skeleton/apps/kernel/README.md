# Kernel

Kernel is the fixed application composition layer.

Framework provides HTTP primitives, routing, container, middleware pipeline, runtime, and bootstrap. Kernel decides which apps are mounted, where their config files live, and how requests are dispatched to them.

Kernel creates one request-scoped `Session`, but the native PHP session starts only if middleware or application code accesses session state. Stateless requests do not need route flags or separate application definitions.

Application code uses Session by constructor-injecting `Pera\Framework\Security\Session`. Injection alone does not start native storage; the first state operation does. App scaffolds intentionally do not generate a stateful Controller by default, so a newly generated public page or API remains stateless until its code actually uses Session.

See the Framework Security README for direct Controller usage, application-owned Session state, CSRF, testing, and worker lifecycle.

The path and namespace are fixed:

- `apps/kernel`
- `App\Kernel`

`vendor/bin/scaffold-app` registers new `ApplicationDefinition` entries inside the standard markers in `apps/kernel/config/application.php`. If those markers are removed for a custom composition format, scaffold leaves the file untouched and prints the definition to add manually.

`apps/kernel/config/application.php` loads `.env` when it exists. Framework does not read dotenv directly. Environment loading belongs to Kernel/project startup.

Kernel resolves environment values from the process environment first, then `$_ENV`, then `$_SERVER`. An explicitly empty value wins over lower-priority sources and the default; the default is used only when no source contains a scalar value.

Every `ApplicationDefinition` must have a unique `name` and `pathPrefix`. A prefix starts with `/`; only the root prefix may end with `/`, and empty path segments are not canonical. Kernel rejects invalid definitions before loading route or middleware configuration files.
