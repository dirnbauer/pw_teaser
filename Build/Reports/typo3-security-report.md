# TYPO3 security report

## Scope

- `Classes/Domain/Repository/`
- `Classes/Domain/Model/`
- `Classes/Controller/`
- `Classes/Utility/`
- `Resources/Private/`
- `ext_localconf.php`

## Summary

No direct SQL injection, raw Fluid output, or custom CSRF surface was found in
the extension code reviewed for TYPO3 13.

## Findings

### 1. Raw record lookups should explicitly ignore deleted rows

- `Classes/Domain/Repository/PageRepository.php`
- `Classes/Domain/Model/Page.php`
- `Classes/Domain/Model/Content.php`

The extension now uses QueryBuilder-based raw lookups for translated pages and
template helper data. These queries should explicitly constrain `deleted = 0`
so deleted records cannot be surfaced accidentally through helper APIs or
translation checks.

### 2. Template rendering currently relies on Fluid auto-escaping

- `Resources/Private/Templates/Teaser/Index.html`

No `f:format.raw()` or similar unsafe output helpers were found, which is the
correct default. This should remain unchanged.

### 3. TypoScript rendering is integrator-controlled, not end-user input

- `Classes/Utility/Settings.php`

`cObjGetSingle()` is used to render TypoScript configuration values. This is an
extension design choice rather than an immediate vulnerability, but it means the
extension should continue to treat TypoScript as trusted integrator input only.

## Recommended changes

1. Add `deleted = 0` constraints to raw page/content record lookups.
2. Add `deleted = 0` constraints to translation existence checks and translated
   PID lookups.
