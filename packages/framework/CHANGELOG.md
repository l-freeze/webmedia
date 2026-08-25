# Changelog

Notable changes to `pera/framework` are documented in this file.

The project follows Semantic Versioning after `1.0.0`. Before `1.0.0`, public API changes may be released in a minor version and will be described here with migration guidance.

## Unreleased

### Added

- Composer library package boundary under `Pera\Framework`.
- Explicit `scaffold-kernel`, `scaffold-shared`, and `scaffold-app` vendor binaries.
- `bare`, DB-free `minimal`, SQLite-backed `todo`, and SQLite-backed `layered` app starters.
- Generated-capability Composer recipes for Dotenv, Latte, PDO, and the SQLite PDO driver.
- Automatic PSR-4 registration for generated Kernel, Shared, and app source roots.
- Automatic app registration inside a marker-bounded standard Kernel configuration, with a non-mutating fallback for custom Kernels.
- Atomic scaffold publishing with non-overwrite and rollback behavior.
- Versioned Kernel, Shared, and app template files with strict placeholder rendering.
- Runnable `bare` starter with one plain-text route and no Shared, template-engine, or database dependency.
- Runnable `layered` contact flow with Latte rendering, Shared validation, and CSRF protection.
- Lazy native Session startup on first state access.
- Classic PHP and FrankenPHP worker runtimes.
- HTTP, routing, middleware, DI container, CSRF, view contract, logging placeholder, and bounded synchronous process primitives.
- Package-owned PHPUnit and PHPStan configuration for testing Framework and scaffold behavior independently from a consuming project.
- Complete installation and scaffolding guide covering published packages, standard and custom Kernel composition, and every supported app template.

### Changed

- The Framework package is distributed under the MIT License.
- Generated Kernel, Shared, and app code is owned entirely by the consuming project and is never updated automatically by Framework releases.
- PDO composition belongs to DB-backed apps or the consuming project; Framework and the generic Kernel scaffold do not own a database backend.

### Fixed

- Native Session start, ID regeneration, close, and destroy failures now raise an explicit `RuntimeException` instead of being treated as successful lifecycle operations.
- 405 responses now include the FastRoute-provided allowed methods in the `Allow` header.
- `scaffold-app` now rejects the fixed infrastructure names `kernel` and `shared` before modifying project files.
- Generated Kernel source now resolves process environment and Dotenv values through one reader with explicit precedence and empty-value semantics. Existing generated Kernels can adopt `src/Environment.php` and replace local environment reads when needed.
- Generated Kernel source now rejects duplicate application names, duplicate path prefixes, and non-canonical prefixes before loading app configuration files. Existing generated Kernels remain consumer-owned and require the same validation to adopt this fix.
