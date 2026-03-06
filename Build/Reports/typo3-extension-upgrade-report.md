# TYPO3 13 upgrade report

## Scope

- Extension: `pw_teaser`
- Current support window: TYPO3 10/11
- Target support window: TYPO3 13.4 LTS only

## Confirmed TYPO3 13 blockers

### 1. Bootstrap constants and backend checks

- `ext_localconf.php` still uses `TYPO3_MODE`.
- TYPO3 13 requires `defined('TYPO3') or die();` in bootstrap files and no
  `TYPO3_MODE` branching there.

### 2. Extbase controller response contract

- `Classes/Controller/TeaserController.php` still exposes an action that may
  return `void`.
- TYPO3 13 Extbase actions must always return `ResponseInterface`, usually via
  `htmlResponse()`.

### 3. TSFE-dependent page access

- `TeaserController`, `Page`, `Content`, and `PageRepository` still read from
  `$GLOBALS['TSFE']`.
- TYPO3 13 prefers request attributes such as `routing` and
  `frontend.page.information`, and TSFE access should be reduced or removed.

### 4. Removed recursive page-tree API

- `Classes/Domain/Repository/PageRepository.php` still uses
  `ContentObjectRenderer::getTreeList()`.
- TYPO3 13 requires `PageRepository->getPageIdsRecursive()` or
  `getDescendantPageIdsRecursive()` instead.

### 5. Fluid internals no longer supported

- `Classes/ViewHelpers/GetContentViewHelper.php` still uses
  `templateVariableContainer`.
- This must be replaced with modern rendering context variable access.

### 6. FlexForm XML shape is outdated

- `Configuration/FlexForms/flexform_teaser.xml` still uses `TCEforms` wrappers.
- TYPO3 13 no longer evaluates those wrappers; labels, `sheetTitle`,
  `displayCond`, and `config` must live directly on the relevant nodes.

### 7. Local development settings are too old

- `.ddev/config.yaml` still pins PHP `7.4` and only advertises TYPO3 10/11
  hostnames and install flows.
- TYPO3 13 requires PHP 8.2+.

## Required upgrade changes

1. Update version constraints and PHP requirements in `composer.json`,
   `ext_emconf.php`, and DDEV config.
2. Remove `TYPO3_MODE` and make `TeaserController::indexAction()` fully
   PSR-7-compliant.
3. Replace TSFE-dependent page and content lookups with TYPO3 13-compatible
   APIs or explicit repository helpers.
4. Replace `getTreeList()` and old DBAL execution patterns in repositories.
5. Refactor `GetContentViewHelper` to work with modern Fluid.
6. Rewrite the FlexForm file into TYPO3 13 syntax.
