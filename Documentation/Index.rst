.. include:: Includes.txt


.. _start:

=============================================================
Page Teaser (with Fluid)
=============================================================

.. only:: html

	:Classification:
		pw_teaser

	:Version:
		|release|

	:Language:
		en

	:Description:
		Create powerful, dynamic page teasers with data from page properties and its content elements. Based on Extbase and Fluid Template Engine.

	:Keywords:
		TYPO3 CMS, teaser, pages, sitemap, pagination

	:Copyright:
		2010-2026

	:Author:
		Armin Vieweg

	:Email:
		armin@v.ieweg.de

	:License:
		This document is published under the Open Content License
		available from http://www.opencontent.org/opl.shtml

	:Rendered:
		|today|

	This manual documents the TYPO3 13.4 LTS / 14.3 LTS baseline of
	``pw_teaser`` (version 7.1). PHP 8.3 or newer is required.

	**Features**

	- Six page sources: direct children, recursive trees or hand-picked pages
	- Category filter with AND, OR and NOT logic
	- Ordering by title, dates, manual sorting, custom fields or random
	- Three template modes (preset, file, directory) for Fluid templates
	- Built-in pagination (``SimplePagination``, optional
	  ``georgringer/numbered-pagination``)
	- PSR-14 ``ModifyPagesEvent`` to filter, sort or enrich the result

	The content of this document is related to TYPO3,
	a GNU/GPL CMS/Framework available from `www.typo3.org <https://www.typo3.org/>`_.


	**Table of Contents**

.. toctree::
        :maxdepth: 2
        :titlesonly:

        Installation/Index
        FirstSteps/Index
        Configuration/Index
        UsingTypoScript/Index
        Templates/Index
        Events/Index
        Testing/Index
        Versions/Index
        Upgrading/Index
        Support/Index
