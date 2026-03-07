.. include:: ../Includes.txt

.. _using-typoscript:


Using TypoScript
================

.. contents:: :local:


Predefine settings with TS
--------------------------

You may predefine some settings which are used in pw_teaser, unless they **will be overwritten by plugin settings**.

To do this, just write this in TypoScript setup:

::

    plugin.tx_pwteaser.settings.[setting] = value

    plugin.tx_pwteaser.view.[setting] = value


.. hint::
   See the configuration :ref:`configuration_reference`, which settings are available.


Pagination class
----------------

By default, pw_teaser uses TYPO3 core's ``SimplePagination`` (previous/next).
To switch to numbered pagination, install ``georgringer/numbered-pagination``
and set:

::

    plugin.tx_pwteaser.settings.paginationClass = GeorgRinger\NumberedPagination\NumberedPagination


Using parsed TypoScript
-----------------------

You may use TypoScript for any pw_teaser setting.

**For example:**

::

    plugin.tx_pwteaser.settings {
      source = customPages
      customPages = CONTENT
      customPages {
        table = pages
        select {
          pidInList = 1
          recursive = 50
          where = whatever=1337
        }
        renderObj = COA
        renderObj.10 = TEXT
        renderObj.10.field = uid
        renderObj.10.wrap = | ,|*| ,| |*|
      }
    }

This example creates a comma-separated list for the setting "customPages". Unlike default Extbase behavior, the
defined settings are parsed by TypoScript parser, before used in Extbase controller.
