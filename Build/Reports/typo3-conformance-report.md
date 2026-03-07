# TYPO3 13 Conformance Report (re-audit)

## Findings

### MEDIUM: PageRepository and ContentRepository not `final`

All other classes in the extension use `final`. These two repositories are
left non-final, which is inconsistent. Since they are not designed as
extension points, they should be `final`.

**Action:** Add `final` keyword to both repository classes.

### MEDIUM: Missing return types and parameter types in PageRepository

Several methods lack native PHP return types or use untyped parameters:
- `orderByPlugin()` — missing return type
- `setFilteredDokType()` — missing return type
- `handleOrdering()` — missing return type
- `resetQuery()` — missing return type
- `addQueryConstraint()` — missing return type

**Action:** Add native types.

### LOW: `RemoveWhitespacesViewHelper::render()` does not handle null children

`renderChildren()` may return `null`. The `str_replace()` call on line 32
will accept null in PHP 8.2 with a deprecation warning.

**Action:** Cast `renderChildren()` to string.

### LOW: `ext_localconf.php` still contains list_type TypoScript alias

The line `tt_content.list.20.pwteaser_pi1 =< tt_content.pwteaser_pi1`
provides backward compatibility for old list_type records. This is
reasonable during the transition period but should be documented
as temporary.

**Action:** Add inline comment explaining it is transitional.

### OK: Conformance items passing

- `declare(strict_types=1)` on all PHP files
- PSR-4 autoloading correct
- DI via `Services.yaml` with autowire/autoconfigure
- PSR-14 event (`ModifyPagesEvent`) properly structured
- PHPStan level 5 passing
- CI pipeline with PHP 8.2–8.4 matrix
- `guides.xml` present for modern doc rendering
