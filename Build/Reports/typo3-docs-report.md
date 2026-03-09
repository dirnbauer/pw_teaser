# TYPO3 Documentation Report (2026-03-06)

## Findings

### MEDIUM: Repository links are inconsistent with active project remote

Multiple docs still point to `github.com/a-r-m-i-n/pw_teaser` while the active
repository is `github.com/dirnbauer/pw_teaser`.

Affected locations:

- `README.md` links section
- `Documentation/Support/Index.rst` issue tracker/contribution links
- `composer.json` support URLs (should align with docs links)

**Action:** update all repository/support URLs to the active remote.

### LOW: Testing guidance is present but not discoverable as a docs chapter

Testing instructions exist in `Documentation/Support/Index.rst`, but there is
no dedicated chapter in the main documentation toctree. This makes CI/testing
guidance harder to find in rendered docs.

**Action:** add `Documentation/Testing/Index.rst` and include it in
`Documentation/Index.rst` toctree.

### OK: Core TYPO3 docs baseline

- `guides.xml` is present
- installation/configuration/upgrading/event docs exist
- TYPO3 13/14 compatibility context is documented

## Suggested Remediation

1. Normalize all repository and issue links to `dirnbauer/pw_teaser`.
2. Add a dedicated Testing chapter and wire it into docs navigation.
