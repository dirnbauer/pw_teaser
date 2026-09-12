# Changelog

All notable changes to `t3/pw_teaser` are documented in this file. Release
notes for versions before 7.0 live in `Documentation/Versions/Index.rst`.

## 7.1.0 – 2026-09-12

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
- Dev dependencies: PHPUnit `^11.5 || ^12.0`, `typo3/testing-framework ^9.0`,
  `saschaegerer/phpstan-typo3 ^3.0`.
- Unit tests use PHPUnit stubs where no expectations are asserted.
- README condensed to the essentials; the detailed material moved to
  `Documentation/`.

### Removed

- Remotion product-tour video sources (`remotion/`), narration and music
  generation scripts (`scripts/`), `package.json` and the video thumbnail.
  The npm ecosystem was dropped from Dependabot.
- Audit reports under `Build/Reports/` are no longer versioned.
- Orphaned screenshot of the removed static-template include.

## 7.0.0 – 2026-03-07

- TYPO3 13.4 / 14.0 compatibility, PHP 8.2–8.4, Fluid 5 readiness, CType
  migration wizard, configurable pagination class, PHPStan level 9.
  Full list: `Documentation/Versions/Index.rst`.
