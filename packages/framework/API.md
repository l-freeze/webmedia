# Public API and Compatibility

## Public surface

The public Framework API consists of the non-internal classes, interfaces, methods, and constants under `Pera\Framework` that are documented in this package. The commands exposed through the package `bin` list and their documented options are also public integration surfaces.

Generated files are not Framework runtime API. Kernel, Shared, and app scaffolds become consumer-owned source as soon as they are generated. A Framework update does not overwrite or migrate them.

## Versioning

`pera/framework` uses Semantic Versioning from `1.0.0` onward.

- Patch releases fix defects without intentionally changing supported public behavior.
- Minor releases add backward-compatible capabilities.
- Major releases may remove or change public behavior and include migration notes.

Before `1.0.0`, a minor release may change public API. Such changes must be recorded in `CHANGELOG.md` with an explicit migration path. Patch releases remain backward-compatible bug fixes.

## Scaffold compatibility

Scaffold changes affect only future generation. Existing generated code remains unchanged. If a security or correctness fix also applies to consumer-owned generated code, the release notes must identify the affected generated files and provide a manual change users can review.

The package does not provide an automatic generated-code upgrader. Consumers that substantially customize generated code should treat release notes as review input rather than an executable patch.

## Extension boundary

Framework owns HTTP primitives, routing, middleware execution, DI, runtime selection, Session and CSRF primitives, the template renderer interface, and bounded process execution. Template engines, environment loaders, database drivers, persistence policy, application validation, authentication, authorization, audit policy, and business rules belong to the consuming project or another package.
