.. _firstSteps:


First steps
===========

Create the first teaser
-----------------------

Open the page that should show the teaser list and add a new content element.
In TYPO3 13 and 14 the plugin appears in the content element wizard as
:guilabel:`Page Teaser (pw_teaser)`.

Its settings are split over three tabs: :guilabel:`General`,
:guilabel:`Ordering` and :guilabel:`Template`.


Define the data source
----------------------

On the :guilabel:`General` tab, pick the **teaser source**:

- Child pages of the current page
- Child pages of the current page (recursively)
- Selected pages
- Child pages of selected pages
- Child pages of selected pages (recursively)

For every source except the first two, the field :guilabel:`Custom pages`
appears after saving. It holds the pages you pick from the page tree.


Make further options
--------------------

The :guilabel:`General` tab also filters the result (categories, doktypes,
ignored pages, hidden pages) and controls the pagination; the
:guilabel:`Ordering` tab changes the order of the pages, and the
:guilabel:`Template` tab selects one of the template presets.

See :ref:`configuration_reference` for every single setting.


See the teaser in the frontend
------------------------------

**That's it!** The shipped templates render a linked page list.

See :ref:`templates` for how to provide your own Fluid templates.
