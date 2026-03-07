# TYPO3 Extension Upgrade Report (re-audit)

Audited against TYPO3 13.4 LTS on the current codebase state.

## Findings

### CRITICAL: `Configuration/TypoScript/setup.txt` contains deprecated Bootstrap->run

`setup.txt` still ships a `lib.tx_pwteaser = USER` block that invokes
`TYPO3\CMS\Extbase\Core\Bootstrap->run`. This userFunc-based rendering was
removed in TYPO3 12. The extension already registers via
`PLUGIN_TYPE_CONTENT_ELEMENT`, making this block dead and potentially
conflicting.

The file also contains `plugin.tx_pwteaser.view.presets` which IS needed
by `ItemsProcFunc::getAvailableTemplatePresets()`.

**Action:** Rename `setup.txt` → `setup.typoscript`, remove the deprecated
`lib.tx_pwteaser` block, keep the presets.

### HIGH: `hidePagesIfNotTranslatedByDefault` global removed in TYPO3 10

`PageRepository::handlePageLocalization()` line 345 reads
`$GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault']`.
This config key was removed in TYPO3 10. In TYPO3 13 it is always
null/false, so the `else` branch is dead code.

**Action:** Remove the obsolete config check; keep only the behavior for
`hidePagesIfNotTranslatedByDefault = false` (the only path that executes).

### LOW: `Services.php` SingletonPass for ItemsProcFunc

The `SingletonPass` compiler pass in `Configuration/Services.php` tags
`ItemsProcFunc` as a singleton. Since `ItemsProcFunc` is now a `final readonly`
class with no mutable state, this is harmless but unnecessary complexity.

**Action:** Remove `Services.php`; `Services.yaml` autowiring is sufficient.

### OK: No remaining deprecated API usage

- No `TYPO3_MODE`, `$GLOBALS['TSFE']`, `deprecationLog`, `getContentObject()`
- FlexForms use modern `label`/`value` items format
- Persistence mapping is in `Configuration/Extbase/Persistence/Classes.php`
- Icons registered via `Configuration/Icons.php`
- CType migration wizard is present

## Status

| Area | Status |
|------|--------|
| composer.json constraints | OK |
| ext_emconf.php constraints | OK |
| ext_localconf.php | OK |
| FlexForm structure | OK |
| Persistence mapping | OK (PHP config) |
| Icon registration | OK |
| TypoScript loading | NEEDS FIX (setup.txt) |
| Deprecated globals | NEEDS FIX (hidePagesIfNotTranslatedByDefault) |
| Services DI | LOW (SingletonPass removable) |
