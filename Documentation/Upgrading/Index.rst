.. _upgrading:


Upgrading
=========

.. contents:: :local:


Upgrading to version 8.1
------------------------

Version 8.1 restructures the internals. Templates, TypoScript, FlexForm
settings and the ``ModifyPagesEvent`` are unchanged; **only PHP code that
called internal classes of the extension needs attention**.

Internal classes that moved or disappeared
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 50 50

   * - Removed in 8.1
     - Replacement
   * - ``Classes/Utility/Settings.php``
     - ``Settings\SettingsRenderer`` (TypoScript fallback and cObject
       rendering) and ``Settings\TeaserSettings`` (typed plugin settings)
   * - ``Domain/Repository/CategoryRepository.php``
     - none — the category filter is built directly in
       ``PageRepository::filterConstraints()`` from the ``CategoryMode`` enum
   * - all public ``PageRepository`` methods
     - ``findChildren()``, ``findDescendants()``, ``findByUids()``, each
       taking a ``Domain\Repository\PageFilter``
   * - ``Resources/Private/Partials/formErrors.html``
     - none — the partial was never rendered
   * - ``Resources/Private/Templates/ViewHelpers/Widget/Paginate/Index.html``
     - ``Resources/Private/Partials/Pagination.html``

New classes: ``Database\RecordRowLoader`` (raw rows behind
``{page.get.columnName}``), ``Domain\Repository\CategoryMode``,
``Domain\Repository\PageFilter``, ``Settings\TeaserSource`` and
``View\TemplateConfiguration``.

Behaviour changes
~~~~~~~~~~~~~~~~~

- Pages selected with source ``custom`` are now resolved through their
  translation in non-default languages, so a translated page is rendered with
  its translated title instead of being dropped.
- Child pages of a **translated** parent page are found again; previously the
  parent uid was translated before being used as ``pid``, which returned no
  results on multilingual sites.
- Non-numeric and ``0`` entries in ``customPages``, ``ignoreUids``,
  ``showDoktypes`` and ``categoriesList`` are dropped instead of being read as
  uid ``0``.


Upgrading to version 8.0
------------------------

Version 8.0 confirms support for TYPO3 **13.4 LTS and 14.3 LTS** and requires
**PHP 8.3 or newer** (8.3, 8.4, 8.5). PHP 8.2 is no longer supported.


Static TypoScript template removed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The static template :guilabel:`PwTeaser` (``Configuration/TCA/Overrides/
sys_template.php``) is no longer registered. Include the TypoScript through
the site set ``t3/pw-teaser`` or, in ``sys_template`` based setups, with

.. code-block:: typoscript

   @import 'EXT:pw_teaser/Configuration/TypoScript/setup.typoscript'

See :ref:`installation-typoscript`. Without one of these the template preset
dropdown is empty and the plugin renders nothing.


Other changes
~~~~~~~~~~~~~

- ``composer.json`` requires ``php: ^8.3`` and
  ``typo3/cms-core: ^13.4 || ^14.3``
- ``ext_emconf.php`` declares ``php: 8.3.0-8.5.99``
- PHPStan runs at level 8 (was level 9)
- No template, TypoScript or PHP API changes: custom templates and event
  listeners written for 7.0 keep working


Upgrading to version 7.0
------------------------

Version 7.0 widens TYPO3 support to **13 LTS and 14** and requires
**PHP 8.2 or newer**. PHP 7.x is no longer supported.

Key changes:

- ``composer.json`` requires ``typo3/cms-core: ^13.4 || ^14.0``
- ``ext_emconf.php`` declares ``typo3: 13.4.0-14.99.99``
- Fluid 5.0 compatibility for TYPO3 14: strict types in ViewHelpers,
  no ``StandaloneView``/``TemplateView`` imports
- ``#[Validate]`` attributes use TYPO3 13/14-compatible array syntax
  (``['validator' => 'NotEmpty']``)
- Configurable pagination class via
  ``plugin.tx_pwteaser.settings.paginationClass``
- ``georgringer/numbered-pagination`` added as a Composer ``suggest``

If you use **custom Fluid templates**, verify that:

1. No variable names start with an underscore (disallowed in Fluid 5.0)
2. Custom ViewHelpers register arguments via ``initializeArguments()``
   instead of ``render()`` method parameters
3. No CDATA sections are used (they are no longer stripped in Fluid 5.0)


CType migration wizard
~~~~~~~~~~~~~~~~~~~~~~

Installations upgrading from an older pw_teaser that still uses
``list_type = pwteaser_pi1`` should run the **PwTeaserCTypeMigration** upgrade
wizard from the TYPO3 Install Tool. This converts old ``list_type`` records to
the modern dedicated ``CType``.

A transitional TypoScript alias (``tt_content.list.20.pwteaser_pi1``) is
included so unmigrated records still render, but the wizard should be run for
a clean setup.


Upgrading from below version 6
------------------------------

Version 6 introduced the pagination and event changes that still matter when
upgrading older installations to the TYPO3 13/14 baseline.
Review custom templates and site configuration before switching an older
project to the current extension version.


Pagination
~~~~~~~~~~

pw_teaser used to rely on the paginate Fluid widget provided by TYPO3 CMS.
Those widgets have been removed from core, so custom templates must use the
pagination data prepared by the extension instead.

Here is a minimum example, which replaces the previous ``widget.paginate`` call:

.. code-block:: html

	<f:variable name="pages">{pages}</f:variable>
	<f:if condition="{settings.enablePagination}">
		<f:variable name="pages">{pagination.paginator.paginatedItems}</f:variable>
	</f:if>

	<f:for each="{pages}" as="page">
		<div>{page.title}</div>
	</f:for>

    <f:if condition="{settings.enablePagination}">
		<f:render partial="Pagination" arguments="{pagination: pagination.pagination, paginator: pagination.paginator}" />
	</f:if>


You can disable the pagination in the plugin settings.
By default it is enabled and the amount of ``settings.itemsPerPage`` is 10.


Routing configuration for pagination
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Copy the following routing enhancer config to your site configuration, to get beautified page links.

.. code-block:: yaml

	routeEnhancers:
	  PwTeaser:
		type: Extbase
		extension: PwTeaser
		plugin: Pi1
		routes:
		  - routePath: '/'
			_controller: 'Teaser::index'
		  - routePath: '/{label-page}-{page}'
			_controller: 'Teaser::index'
			_arguments:
			  page: 'currentPage'
		defaultController: 'Teaser::index'
		defaults:
		  page: '0'
		requirements:
		  page: '\d+'
		aspects:
		  page:
			type: StaticRangeMapper
			start: '1'
			end: '999'
		  label-page:
			type: LocaleModifier
			default: 'page'
			localeMap:
			  -   locale: 'de_.*'
				  value: 'seite'


Events
~~~~~~

Previous versions of pw_teaser provided a Signal to programmatically modify the
page result array. Since version 6, those Signals have been replaced with
:ref:`events`.
