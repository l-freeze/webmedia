# Security Policy

## Reporting a vulnerability

Do not disclose a suspected vulnerability in a public issue before a fix is available. Use the private vulnerability-reporting channel provided by the source repository hosting `pera/framework`. Include the affected version, impact, reproduction steps, and any known mitigation.

The final public repository must enable a private security-reporting channel before the first public release. Its concrete URL will be added here when the repository is created.

## Supported versions

Before the first tagged release, only the current development branch is evaluated. A supported-version table will be added when the initial release line is chosen. Production users should run a tagged release rather than a development branch.

## Generated code

Kernel, Shared, and app scaffold output is consumer-owned and is not replaced by a Framework update. When a vulnerability affects generated code, the corresponding security advisory or changelog entry will identify the affected scaffold version and provide manual remediation instructions.

Framework does not know or configure the consumer's database backend, reverse proxy trust policy, authentication policy, authorization rules, secret storage, deployment topology, or operational controls. Those remain part of the consuming application's security boundary.
