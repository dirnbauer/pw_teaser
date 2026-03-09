# TYPO3 Security Report (2026-03-06)

Scope: TYPO3-specific extension security pass focused on trusted boundaries,
safe rendering, request handling, and extension-owned local tooling.

## Findings

### MEDIUM: Local install script configures overly broad trusted hosts pattern

`.ddev/commands/web/install-v13` sets:

`vendor/bin/typo3 configuration:set SYS/trustedHostsPattern '.*'`

Even in local dev, this pattern disables host-header validation entirely. A
project-specific DDEV wildcard is safer and still practical.

**Action:** constrain to `*.pw-teaser.ddev.site`.

### LOW: `GetContentViewHelper` assumes all `contents` entries are `Content`

The render loop directly calls `$content->getCtype()` and
`$content->getColPos()` for each array item. Non-`Content` values can trigger
runtime errors and potentially produce avoidable DoS in malformed templates.

**Action:** add `instanceof Content` guard and skip invalid entries.

### OK: Core extension security baseline remains strong

- QueryBuilder with named parameters is used for DB access.
- No raw SQL concatenation.
- Frontend action is read-only (no state mutation endpoints).
- No embedded production secrets in extension code.
- No high-risk functions (`eval`, `exec`, `unserialize`) in extension runtime.

## Suggested Remediation

1. Restrict trusted hosts in DDEV install script to extension-specific wildcard.
2. Harden `GetContentViewHelper` iteration with type guards.
3. Add unit coverage for invalid collection entries to prevent regressions.
