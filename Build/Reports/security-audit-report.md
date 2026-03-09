# OWASP Security Audit Report (2026-03-06)

## Methodology

Reviewed against OWASP Top 10 priorities for PHP/TYPO3 extension code:
injection, XSS, CSRF, trust boundaries, vulnerable dependencies, and secure
operational defaults.

## Findings

### LOW: Dependency update hygiene can be improved (A06)

The repository currently has no automated dependency update workflow
(`dependabot.yml` missing). This increases the chance of delayed security
updates in development dependencies and CI tooling.

**Action:** add a weekly GitHub Dependabot config for Composer and npm.

### OK: Injection resistance (A03)

- DB access uses QueryBuilder + named parameters.
- No raw SQL interpolation found in extension runtime code.

### OK: XSS posture (A03)

- Fluid auto-escaping is used by default.
- No extension templates rely on `f:format.raw`.
- `GetContentViewHelper` is now guarded against invalid entries.

### OK: CSRF and state mutation surface (A01/A07)

- Frontend controller action is read-only (`indexAction`).
- No extension-owned mutating endpoints or backend form handlers in scope.

### OK: Unsafe parsing / code execution (A08)

- No `eval`, `exec`, `shell_exec`, `passthru`, or `unserialize` usage in
  extension runtime code.

## Suggested Remediation

1. Add `.github/dependabot.yml` for Composer + npm ecosystem updates.
2. Keep CI green so dependency PRs can be merged quickly.
