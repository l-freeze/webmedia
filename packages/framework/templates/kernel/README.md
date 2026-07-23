# Kernel

Kernel is the fixed application composition layer.

Framework provides HTTP primitives, routing, container, middleware pipeline, runtime, and bootstrap. Kernel decides which apps are mounted, where their config files live, and how requests are dispatched to them.

Kernel creates one request-scoped `Session`, but the native PHP session starts only if middleware or application code accesses session state. Stateless requests do not need route flags or separate application definitions.

Application code uses Session by constructor-injecting `Pera\Framework\Security\Session`. Injection alone does not start native storage; the first state operation does. App scaffolds intentionally do not generate a stateful Controller by default, so a newly generated public page or API remains stateless until its code actually uses Session.

See the Framework Security README for direct Controller usage, application-owned Session state, CSRF, testing, and worker lifecycle.

The path and namespace are fixed:

- `apps/kernel`
- `App\Kernel`

`apps/kernel/config/application.php` contains Pera application markers. `vendor/bin/scaffold-app` registers new `ApplicationDefinition` entries between those markers. The file remains consumer-owned: if you remove the markers or replace the composition format, scaffold leaves it unchanged and prints the definition to add manually.

`apps/kernel/config/application.php` loads `.env` when it exists. Framework does not read dotenv directly. Environment loading belongs to Kernel/project startup.
