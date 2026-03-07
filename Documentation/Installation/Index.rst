.. include:: ../Includes.txt

.. _installation:


Installation
============

Requirements
------------

- TYPO3 13.4 LTS or TYPO3 14
- PHP 8.2 or newer (8.2, 8.3, 8.4)

Download
--------

You can use the TER (TYPO3 Extension Repository) or Composer to fetch ``t3/pw_teaser`` package.

- TER: https://extensions.typo3.org/extension/pw_teaser
- Packagist: https://packagist.org/packages/t3/pw_teaser

For Composer-based installations, run:

.. code-block:: bash

    composer require t3/pw_teaser

After installation, enable the extension in TYPO3 if needed:

.. code-block:: bash

    vendor/bin/typo3 extension:activate pw_teaser


TypoScript Setup
----------------

When pw_teaser is successfully installed, you need to **include the provided TypoScript** to your TypoScript template:

In TYPO3 13 or 14, include the static TypoScript record :guilabel:`PwTeaser` in your
site template so the default view configuration and template presets are loaded.


.. important::
   When you don't include the pw_teaser TypoScript, plugin settings like "Template preset" remain empty.


That's it. Now, pw_teaser is ready to get used.
