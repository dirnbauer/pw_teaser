# Changelog

All notable changes to `t3/pw_teaser` are documented in this file. Release
notes for versions before 7.0 live in `Documentation/Versions/Index.rst`.

## 8.1.0 – 2026-09-19

Behaviour-preserving restructuring plus three multilingual bug fixes. Fluid
templates, TypoScript, FlexForm settings and `ModifyPagesEvent` are unchanged.

### Added

- `Settings\TeaserSettings` (typed, validated plugin settings),
  `Settings\TeaserSource` and `Domain\Repository\CategoryMode` enums,
  `Domain\Repository\PageFilter`, `View\TemplateConfiguration` and
  `Database\RecordRowLoader`.
- Functional tests for `ContentRepository` and `RecordRowLoader`, unit tests
  for every new class, and functional coverage for all four category modes,
  `l18n_cfg` translation visibility and language overlays.

### Changed

- `PageRepository` rewritten around `PageFilter`; every find method builds its
  own query, so the repository holds no request state (661 → 313 lines). Its
  public API is now `findChildren()`, `findDescendants()` and `findByUids()`.
- `TeaserController` reduced to orchestration (498 → 221 lines).
- Repositories build their query settings per query instead of freezing them
  at construction time.
- PHPUnit configurations moved to `Build/phpunit/`; the workflow is now
  `.github/workflows/ci.yml` and functional CI runs against MariaDB 10.11.

### Removed

- `Classes/Utility/Settings.php`, `Classes/Domain/Repository/CategoryRepository.php`,
  the unrendered partials `formErrors.html` and
  `Templates/ViewHelpers/Widget/Paginate/Index.html`, the unused `alias`
  property and the `L18N_*` constants of the page model.

### Fixed

- Child pages of a **translated** parent page are found again: page
  translations keep the `pid` of their original, so the parent uid must not be
  translated before it is used as `pid`.
- Hand-picked pages (source `custom`) resolve to their translation in every
  language, so they are rendered with the translated title instead of being
  dropped.
- Non-numeric and `0` entries in `customPages`, `ignoreUids`, `showDoktypes`
  and `categoriesList` are dropped instead of being read as uid `0`.

### Known issues

- On TYPO3 14.3 the presence of `ext_emconf.php` triggers a deprecation
  notice. The file is kept on purpose because pw_teaser is published in TER.

## 8.0.0 – 2026-09-12

### Added

- TYPO3 14.3 LTS support confirmed: `typo3/cms-core` and `typo3/cms-install`
  now require `^13.4 || ^14.3`.
- PHP 8.5 support: `php ^8.3`, `ext_emconf.php` constraint `8.3.0-8.5.99`.
  CI runs PHP 8.5 against TYPO3 14.3 as an allowed failure.
- TYPO3 coding standards (`typo3/coding-standards`, `.php-cs-fixer.dist.php`)
  and Composer scripts `ci:lint`, `ci:cgl`, `ci:phpstan`, `ci:test:unit`,
  `ci:test:functional`.
- This changelog.

### Changed

- **Static TypoScript registration removed.**
  `Configuration/TCA/Overrides/sys_template.php` (the `addStaticFile()` call
  that registered the "PwTeaser" static template) is gone. Include the
  TypoScript via the site set `t3/pw-teaser` (`dependencies` in
  `config/sites/<site>/config.yaml`) or, in sys_template-based setups, via
  `@import 'EXT:pw_teaser/Configuration/TypoScript/setup.typoscript'`.
  Installations that used "Include static (from extensions) > PwTeaser" must
  switch, otherwise the template presets are empty.
- Minimum PHP version raised from 8.2 to 8.3 (TYPO3 13 LTS floor).
- PHPStan runs at level 8 (project policy; previously level 9).
- Rector configuration targets PHP 8.3.
- CI matrix: PHP 8.3/8.4 × TYPO3 ^13.4 and PHP 8.4/8.5 × TYPO3 ^14.3 with the
  jobs lint, cgl (dry-run), phpstan (both TYPO3 majors), unit and functional.
- Dev dependencies: PHPUnit `^12.4 || ^13.0`, `typo3/testing-framework ^9.5`,
  `saschaegerer/phpstan-typo3 ^3.0`.
- Unit tests use PHPUnit stubs where no expectations are asserted.
- README condensed to the essentials; the detailed material moved to
  `Documentation/`.

### Removed

- Product-tour video material: the video composition sources, the narration
  and music generation scripts (`scripts/`), `package.json` and the video
  thumbnail. The npm ecosystem was dropped from Dependabot.
- Audit reports under `Build/Reports/` are no longer versioned.
- Orphaned screenshot of the removed static-template include.

## 7.0.0 – 2026-03-07

- TYPO3 13.4 / 14.0 compatibility, PHP 8.2–8.4, Fluid 5 readiness, CType
  migration wizard, configurable pagination class, PHPStan level 9.
  Full list: `Documentation/Versions/Index.rst`.
