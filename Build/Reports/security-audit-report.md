# OWASP Security Audit Report (re-audit)

## Methodology

Checked against OWASP Top 10 for PHP/web applications: injection, XSS,
CSRF, broken access control, security misconfiguration, insecure parsing,
and secrets exposure.

## Findings

### OK: A1 Injection

All database access uses TYPO3 QueryBuilder with `createNamedParameter()`.
No string concatenation in SQL. `GeneralUtility::intExplode()` and
`GeneralUtility::trimExplode()` sanitize user-facing comma-separated
inputs before use.

### OK: A3 Injection / XSS

Fluid templates use auto-escaping by default. The only ViewHelper with
`$escapeOutput = false` is `GetContentViewHelper`, which delegates to
child rendering (controlled by the template author, not user input).
`StripTagsViewHelper` explicitly strips HTML. No `innerHTML` or
`f:format.raw` in extension templates.

### OK: A5 Security Misconfiguration

Extension does not ship production configuration. `SYS/trustedHostsPattern`
is set to `.*` only in the local DDEV install script, not in the extension.

### OK: A7 Cross-Site Request Forgery

Read-only `indexAction` with no form handling or state mutation.

### OK: A8 Insecure Deserialization

No `unserialize()`, `eval()`, `exec()`, `shell_exec()`, `system()`, or
`passthru()` calls in extension code.

### OK: A9 Components with Known Vulnerabilities

`composer.json` constrains to `typo3/cms-core: ^13.4` which receives
active security updates. Dev dependencies (`phpunit`, `rector`, etc.)
are not shipped in production.

### INFORMATIONAL: Magic `__call` methods

`Page::__call()` and `Content::__call()` fetch raw database rows on
cache miss. This is an accepted design pattern documented as `@deprecated`.
No user-controllable input reaches method names in normal Fluid rendering.

## Status

No new actionable issues. No code changes required.
