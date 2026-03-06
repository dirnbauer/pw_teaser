# TYPO3 docs report

## Scope

Review of `README.md`, `Documentation/`, and local docs tooling for TYPO3 13.4
LTS alignment.

## Findings

1. `README.md` still documents TYPO3 10.4 / 11.5 requirements and a TYPO3 10 /
   11 DDEV environment, which no longer matches the extension metadata or local
   setup.
2. `Documentation/Installation/Index.rst` does not state the TYPO3 13.4 / PHP
   8.2 baseline and lacks a current Composer-first installation flow.
3. `Documentation/Upgrading/Index.rst` still describes version 6 as a TYPO3 10
   / 11 release, so the upgrade chapter is stale for the current TYPO3 13
   target.
4. `Documentation/Settings.yml` still carries old Sphinx-era metadata
   (`version: 6.0`, `release: 6.0.3`) and legacy `http://docs.typo3.org/...`
   intersphinx targets.
5. The documentation references screenshot files that are not present in the
   repository:
   - `Documentation/Installation/Images/include-static-typoscript.png`
   - `Documentation/FirstSteps/Images/add-plugin.png`
   - `Documentation/FirstSteps/Images/plugin-sheet-general.png`
   - `Documentation/FirstSteps/Images/custom-pages.png`
   - `Documentation/FirstSteps/Images/default-frontend-output.png`
   - `Documentation/Configuration/Images/settings-general.png`
   - `Documentation/Configuration/Images/settings-visibility.png`
   - `Documentation/Configuration/Images/settings-template.png`
   - `Documentation/Templates/Images/preset-dropdown.png`
   - `Documentation/Templates/Images/directory-structure.png`
6. Local docs rendering was also blocked by an outdated docs container image
   reference in `.ddev/docker-compose.docs.yaml`.

## Recommended fixes

- Update `README.md` and the installation/upgrade chapters to reflect TYPO3
  13.4 LTS and PHP 8.2.
- Keep the current documentation structure, but refresh `Settings.yml` metadata
  to match the current release baseline.
- Remove or replace broken screenshot directives until TYPO3 13 screenshots are
  added back to the repository.
- Keep the docs container image on `ghcr.io/typo3-documentation/render-guides`.
