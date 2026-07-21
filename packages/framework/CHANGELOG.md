# Changelog

Notable changes to `pera/framework` are documented in this file.

The project follows Semantic Versioning after `1.0.0`. Before `1.0.0`, public API changes may be released in a minor version and will be described here with migration guidance.

## Unreleased

### Added

- Composer library package boundary under `Pera\Framework`.
- Explicit `scaffold-kernel`, `scaffold-shared`, and `scaffold-app` vendor binaries.
- `bare`, DB-free `minimal`, SQLite-backed `todo`, and SQLite-backed `layered` app starters.
- Generated-capability Composer recipes for Dotenv, Latte, PDO, and the SQLite PDO driver.
- Atomic scaffold publishing with non-overwrite and rollback behavior.
- Lazy native Session startup on first state access.
- Classic PHP and FrankenPHP worker runtimes.
- HTTP, routing, middleware, DI container, CSRF, view contract, logging placeholder, and bounded synchronous process primitives.

### Changed

- Generated Kernel, Shared, and app code is owned entirely by the consuming project and is never updated automatically by Framework releases.
- PDO composition belongs to DB-backed apps or the consuming project; Framework and the generic Kernel scaffold do not own a database backend.
