# TYPO3 testing report

## Current state

- No `Tests/` directory exists yet.
- No PHPUnit configuration exists.
- No TYPO3 testing framework dependency exists.
- No CI workflow exists for automated verification.

## Minimum viable TYPO3 13 baseline

### Unit tests

- Add a fast unit test for `StripTagsViewHelper`.
- Add a small event-focused unit test for `ModifyPagesEvent`.

### Functional tests

- Add a first functional test around upgraded repository behavior in
  `PageRepository`, focusing on translation detection or recursive page lookup.

### Tooling

- Add TYPO3 13-compatible dev dependencies for PHPUnit and the TYPO3 testing
  framework.
- Add PHPUnit config files for unit and functional suites.
- Add a small GitHub Actions workflow that runs unit and functional tests.

## Recommended changes

1. Create `Tests/Unit/` and `Tests/Functional/`.
2. Add `Tests/UnitTests.xml` and `Tests/FunctionalTests.xml`.
3. Add TYPO3 13-compatible dev dependencies with Composer.
4. Add one functional fixture dataset for page translation/recursion behavior.
5. Add a minimal CI workflow for the new suites.
