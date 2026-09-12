.. include:: ../Includes.txt

.. _installation:


Installation
============

.. contents:: :local:


.. _installation-requirements:

Requirements
------------

.. list-table::
   :header-rows: 1
   :widths: 20 40 40

   * - pw_teaser
     - TYPO3
     - PHP
   * - 7.1
     - 13.4 LTS, 14.3 LTS
     - 8.3, 8.4, 8.5
   * - 7.0
     - 13.4, 14.0
     - 8.2, 8.3, 8.4
   * - 6.x
     - 11, 12, 13
     - 8.1, 8.2, 8.3


.. _installation-composer:

Composer
--------

The Packagist entry ``t3/pw_teaser`` still points to the unmaintained upstream
repository (latest release 6.0.3). Register the maintained fork as a VCS
repository first, then require the package:

.. code-block:: bash

   composer config repositories.pw-teaser vcs https://github.com/dirnbauer/pw_teaser
   composer require t3/pw_teaser

Composer installations activate the extension automatically. Run the database
compare in the Install Tool or ``vendor/bin/typo3 extension:setup`` afterwards.


.. _installation-ter:

TER (classic installation)
--------------------------

Download the extension from https://extensions.typo3.org/extension/pw_teaser or
install it through the Extension Manager and activate it.


.. _installation-typoscript:

TypoScript
----------

pw_teaser ships its TypoScript (view defaults and the template presets) as a
`site set <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SiteHandling/SiteSets.html>`__.
Add it to your site configuration:

.. code-block:: yaml
   :caption: config/sites/<site>/config.yaml

   dependencies:
     - t3/pw-teaser

Alternatively select :guilabel:`PwTeaser – Page Teaser` under
:guilabel:`Site Management > Sites > Edit > Sets for this site`.

Installations that still work with ``sys_template`` records instead of site
sets import the setup file directly in their TypoScript template:

.. code-block:: typoscript

   @import 'EXT:pw_teaser/Configuration/TypoScript/setup.typoscript'

.. important::
   Since version 7.1 the static TypoScript template :guilabel:`PwTeaser` is no
   longer registered (``ExtensionManagementUtility::addStaticFile()`` was
   removed). Installations that used :guilabel:`Include static (from
   extensions)` must switch to one of the two options above; otherwise the
   :guilabel:`Template preset` dropdown of the plugin stays empty and nothing
   is rendered.

That's it. Now, pw_teaser is ready to get used.
