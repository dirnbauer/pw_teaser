# TYPO3 Conformance Report (2026-03-06)

Scope: extension-wide conformance pass for architecture, metadata, typed PHP,
testing baseline, and repository hygiene.

## Findings

### MEDIUM: Composer metadata can be improved for ecosystem conformance

`composer.json` lacks explicit `support` links and package `keywords`, which
reduces quality signals in Packagist and for maintainers.

**Action:** add `support.issues`, `support.source`, and curated keywords.

### LOW: Class constants use implicit visibility

`Page` and `PageRepository` define constants with `const` instead of explicit
`public const`. While valid, explicit visibility is preferred in modern TYPO3
extensions for readability and conformance.

**Action:** switch to `public const` for all model/repository constants.

### OK: Core conformance baseline is strong

- Strict typing is present across extension PHP files.
- PSR-4 autoload and directory structure are coherent.
- Services are wired through `Configuration/Services.yaml`.
- CI matrix validates PHP 8.2/8.3/8.4 with TYPO3 13/14.
- PHPStan is enforced at level 9.
- Unit + functional test baseline is comprehensive (87 tests total).

## Suggested Remediation

1. Enrich `composer.json` metadata (`support`, `keywords`).
2. Make constant visibility explicit (`public const`) in key domain classes.
