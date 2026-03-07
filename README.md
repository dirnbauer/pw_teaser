# pw_teaser for TYPO3 CMS

Create powerful page teasers in TYPO3 CMS with data from page properties and
its content elements.
Based on Extbase and Fluid template engine.


## Features

- Show lists of pages (like TYPO3's ``menu_pages`` or ``menu_subpages`` content element type)
- Very detailed options to filter pages
- Create nested or flat views of your page structures
- Layout the teasers of your pages like you want, with Fluid templates
- Template Presets
- Pagination available
- Extendable by EventListener, to modify or extend pages result


## Documentation

This extension provides ReST documentation in the
[`Documentation/`](Documentation) directory.

You can see a rendered HTML version on https://docs.typo3.org/p/t3/pw_teaser/main/en-us/


## Requirements

The current development baseline targets TYPO3 13.4 LTS and PHP 8.2.

### CategoryRepository shim

TYPO3 removed `\TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository` from
core in version 12. Because pw_teaser needs to look up `Category` objects by UID
for its category-filter feature, the extension ships a minimal local replacement
at `Classes/Domain/Repository/CategoryRepository.php`. It extends the standard
Extbase `Repository` and sets the object type to `Category` — nothing else.


## How to contribute?

Just fork this repository and create a pull request to the **master** branch.
Please also describe why you've submitted your patch. If you have any questions feel free to contact me.

In case you can't provide code but want to support pw_teaser anyway, here is my
[PayPal donation link](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2DCCULSKFRZFU).

### DDEV Environment

pw_teaser ships a mostly standard DDEV configuration for TYPO3 extension
development with PHP 8.2 and an isolated TYPO3 13 instance in a dedicated
`v13` data volume.

Start the environment with `ddev start` and create the TYPO3 13 test instance
with `ddev install-v13`. The TYPO3 test instance is then available at
`https://v13.pw-teaser.ddev.site/`.

Run the automated checks inside DDEV with `ddev test-unit` and
`ddev test-functional`.


## Links

- [Git Repository](https://github.com/a-r-m-i-n/pw_teaser)
- [Issue tracker](https://github.com/a-r-m-i-n/pw_teaser/issues)
- [Read documentation online](https://docs.typo3.org/p/t3/pw_teaser/main/en-us/)
- [EXT:pw_teaser in TER](https://extensions.typo3.org/extension/pw_teaser)
- [EXT:pw_teaser on Packagist](https://packagist.org/packages/t3/pw_teaser)
- [The author](https://v.ieweg.de)
- [**Donate**](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2DCCULSKFRZFU)
