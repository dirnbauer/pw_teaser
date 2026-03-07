# TYPO3 Security Report (re-audit)

## Findings

### OK: QueryBuilder with parameterized queries throughout

All database access in `PageRepository`, `Page::getGet()`, and
`Content::__call()` uses `QueryBuilder` with `createNamedParameter()`.
No raw SQL concatenation.

### OK: Fluid auto-escaping respected

`GetContentViewHelper` sets `$escapeOutput = false` intentionally to
pass through child rendering. All other ViewHelpers use default escaping.
No `f:format.raw` in extension templates.

### OK: No CSRF exposure

The extension only provides a frontend plugin with a read-only `indexAction`.
No form submissions, no data mutation endpoints. CSRF protection is not
applicable.

### OK: No secrets or credentials in extension code

No API keys, passwords, or sensitive configuration embedded in source.
DDEV credentials are local-only test defaults.

### LOW: `__call` magic methods perform DB queries on unrecognized getters

Both `Page::__call()` and `Content::__call()` issue database queries when
Fluid templates call arbitrary `{page.someUnknownProperty}` or
`{content.someUnknownProperty}`. This is by design (dynamic page/content
attribute access), and queries use parameterized access. The methods are
already marked `@deprecated`.

**Action:** No code change needed. Documented as accepted risk.

### LOW: Exception messages include filesystem paths

`TeaserController::performTemplatePathAndFilename()` throws exceptions
containing template/partial/layout directory paths. In production with
`debug=0`, TYPO3 suppresses these. No change needed.

## Status

All extension-level security checks pass. No actionable remediations.
