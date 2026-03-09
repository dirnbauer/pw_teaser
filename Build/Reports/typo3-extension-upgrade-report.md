# TYPO3 Extension Upgrade Report (2026-03-06)

Scope: re-check extension upgrade status for TYPO3 13/14 baseline with focus on
real upgrade regressions and stale migration artifacts.

## Findings

### HIGH: Migration documentation is no longer accurate

The upgrade docs still state `#[Validate]` uses named-argument syntax
(`validator: 'NotEmpty'`). The code now intentionally uses the array-based
syntax (`['validator' => 'NotEmpty']`) for cross-version compatibility in
TYPO3 13/14.

Affected files:

- `README.md`
- `Documentation/Upgrading/Index.rst`
- `Documentation/Versions/Index.rst`

**Action:** Update docs/changelog wording to match the implemented compatibility
strategy.

### MEDIUM: Changelog still mentions old PHPStan baseline

`Documentation/Versions/Index.rst` still says "PHPStan level 5 static analysis"
while the extension is now maintained at level 9.

**Action:** update the 7.0.0 entry to reflect level 9.

### OK: Core upgrade mechanics are in place

- Composer constraints are aligned to TYPO3 13.4/14 and PHP 8.2+
- `ext_emconf.php` dependency ranges are aligned
- Extbase plugin registration uses `PLUGIN_TYPE_CONTENT_ELEMENT`
- TypoScript preset loading exists in `setup.typoscript`
- No legacy `Bootstrap->run` rendering block present
- No deprecated TSFE-style runtime access in extension code

## Suggested Remediation

1. Correct migration docs for `#[Validate]` compatibility syntax.
2. Correct PHPStan level references in the versions/changelog docs.
3. Keep dual-version wording explicit to avoid accidental downgrade assumptions.
