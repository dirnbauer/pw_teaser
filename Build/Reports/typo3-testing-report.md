# TYPO3 Testing Report (2026-03-06)

## Current Test Infrastructure

- **74 unit tests** across controller, models, repositories, user functions,
  utility classes, events, and ViewHelpers
- **14 functional tests** for `PageRepository` behavior and filtering
- **CI matrix** for TYPO3 13/14 and PHP 8.2/8.3/8.4
- **PHPStan level 9** in CI

## Findings

### MEDIUM: `resolveCurrentPageUid()` fallback behavior lacks direct tests

`TeaserController::resolveCurrentPageUid()` has two execution paths:

1. Prefer `frontend.page.information->getId()`
2. Fallback to `routing->getPageId()`

Only indirect coverage exists through `initializeAction` and other controller
tests. A direct unit test is needed to protect request-attribute compatibility
across TYPO3 runtime changes.

**Action:** add dedicated unit tests for both paths and the default `0` case.

### OK: Other high-risk areas are now covered

- `ItemsProcFunc` DI fallback and FlexForm edge cases
- `GetContentViewHelper` filtering/indexing and invalid-entry guard
- `PageRepository` recursive/list/filtering behavior (functional)
- `Page` / `Content` model typed property behavior

## Suggested Remediation

1. Add direct unit tests for `resolveCurrentPageUid()`:
   - page information attribute path
   - routing fallback path
   - default value path
