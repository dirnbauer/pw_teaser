# TYPO3 conformance report

## Scope

- Extension: `pw_teaser`
- Target: TYPO3 13.4 LTS only
- Reviewed areas: `composer.json`, `ext_emconf.php`, `ext_localconf.php`,
  `Classes/`, `Configuration/`, `Documentation/`, `README.md`

## Summary

`pw_teaser` has a solid extension skeleton, PSR-4 autoloading, DI setup, and
structured documentation, but it is not conformant for TYPO3 13 yet.

## Key findings

### 1. Release metadata is outdated

- `composer.json` still requires `typo3/cms-core: ^10.4.6 || ^11.5`.
- `ext_emconf.php` still declares TYPO3 `10.4.6-11.5.99`.
- No explicit PHP runtime requirement is declared in `composer.json`.

### 2. Runtime code still uses legacy TYPO3 patterns

- `ext_localconf.php` uses `TYPO3_MODE` and backend branching that does not fit
  TYPO3 13.
- `Classes/Controller/TeaserController.php` still relies on TSFE-era access,
  weak typing, and conditional response creation.
- `Classes/Domain/Repository/PageRepository.php` still uses TSFE state,
  `ContentObjectRenderer::getTreeList()`, and old DBAL execution style.
- `Classes/ViewHelpers/GetContentViewHelper.php` still uses
  `templateVariableContainer`, which is not valid for modern Fluid.

### 3. PHP quality is inconsistent

- Some files use `declare(strict_types=1);`, but many core classes do not.
- Controller and repository code still use untyped properties, loose return
  types, and non-`final` classes.
- Legacy comments and broad docblocks remain where native PHP types should
  carry intent.

### 4. FlexForm format is not TYPO3 13 ready

- `Configuration/FlexForms/flexform_teaser.xml` still uses `TCEforms`
  wrappers that are removed in TYPO3 13.

### 5. Quality tooling baseline is missing

- No `Tests/` directory exists.
- No PHPUnit configuration exists.
- No CI workflow exists for static analysis or tests.

### 6. Documentation is structured but outdated

- `Documentation/` still uses legacy `Settings.yml` and `Settings.cfg`.
- TYPO3 13 support is not documented in `README.md` or version docs.
- Referenced screenshots are missing from the repository.

## Strengths to keep

- PSR-4 autoloading is already configured.
- `Configuration/Services.yaml` provides a good DI baseline.
- The extension already separates controller, repository, model, and docs
  concerns clearly.

## Recommended remediation order

1. Modernize TYPO3 13 runtime blockers in controller, repository, viewhelper,
   and FlexForm files.
2. Update extension metadata and local development assumptions for TYPO3 13.
3. Add a minimal test baseline and CI support.
4. Refresh documentation to match the TYPO3 13 support window.
