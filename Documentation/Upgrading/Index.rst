.. include:: ../Includes.txt

.. _upgrading:


Upgrading from below version 6
==============================

Version 6 introduced the pagination and event changes that still matter when
upgrading older installations to the TYPO3 13.4 baseline.
Review custom templates and site configuration before switching an older
project to the current extension version.


Pagination
----------

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
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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
------

Previous versions of pw_teaser provided a Signal to programmatically modify the
page result array. Since version 6, those Signals have been replaced with
`Events <events>`_.
