# TYPO3 Testing Report (re-audit)

## Current Test Infrastructure

- **19 unit tests** covering Page, Content, TeaserController, Settings,
  ItemsProcFunc, StripTagsViewHelper, GetContentViewHelper, ModifyPagesEvent
- **2 functional tests** covering PageRepository recursive collection
  and deleted-page filtering
- **PHPStan level 5** with zero errors
- **CI matrix:** PHP 8.2, 8.3, 8.4 for both unit and functional tests
- **DDEV commands:** `ddev test-unit`, `ddev test-functional`

## Findings

### MEDIUM: No test for RemoveWhitespacesViewHelper

The ViewHelper is small but now includes a null-safety cast that should
be covered.

**Action:** Add unit test.

### LOW: No test for PwTeaserCTypeMigration

The upgrade wizard class is thin (delegates to base class) and would
require a functional test with database fixtures. Low value given the
base class is tested by TYPO3 core.

**Action:** Skip — base class coverage is sufficient.

### OK: Test coverage of upgraded behavior

Tests specifically cover:
- Page model null-keyword handling (TYPO3 13 null-safety)
- TeaserController default settings when FlexForm is empty
- GetContentViewHelper index counting with colPos+cType filter (bug fix)
- PageRepository recursive collection with language filtering
- Settings utility TypoScript fallback behavior
- ModifyPagesEvent page replacement

## Status

| Area | Status |
|------|--------|
| Unit tests | 19 passing |
| Functional tests | 2 passing |
| PHPStan | Level 5, zero errors |
| CI pipeline | PHP 8.2–8.4 matrix |
| RemoveWhitespacesViewHelper | NEEDS TEST |
