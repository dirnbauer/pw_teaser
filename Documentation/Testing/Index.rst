.. include:: ../Includes.txt

.. _testing:


Testing and development
=======================

.. contents:: :local:

pw_teaser ships 77 unit tests and 14 functional tests. The Composer scripts
below wrap the tools that CI runs.


Set up
------

.. code-block:: bash

   composer install

   # switch the TYPO3 major (Composer picks the newest allowed by default)
   composer update --with typo3/cms-core:^13.4
   composer update --with typo3/cms-core:^14.3


Run checks locally
------------------

.. code-block:: bash

   composer ci:lint            # php -l on all PHP files
   composer ci:cgl             # php-cs-fixer dry-run (TYPO3 coding standards)
   composer ci:phpstan         # PHPStan level 8
   composer ci:test:unit       # PHPUnit unit tests

Functional tests need a database. SQLite is enough locally:

.. code-block:: bash

   typo3DatabaseDriver=pdo_sqlite composer ci:test:functional

For MySQL or MariaDB set ``typo3DatabaseName``, ``typo3DatabaseHost``,
``typo3DatabaseUsername``, ``typo3DatabasePassword`` and
``typo3DatabaseDriver=mysqli`` instead.

Fix coding-guideline violations with ``vendor/bin/php-cs-fixer fix``.


DDEV environment
----------------

A DDEV configuration with PHP 8.3 and MariaDB is included:

.. code-block:: bash

   ddev start
   ddev install-v13        # TYPO3 13 instance at https://v13.pw-teaser.ddev.site/
   ddev test-unit
   ddev test-functional

If functional tests fail with ``Access denied ... to database db_ft...``,
allow the ``db`` user to create the TYPO3 test databases:

.. code-block:: bash

   ddev mysql -e "GRANT ALL ON \`db_%\`.* TO 'db'@'%'; FLUSH PRIVILEGES;"


Test coverage
-------------

.. list-table::
   :header-rows: 1
   :widths: 25 12 8 55

   * - Component
     - Type
     - Tests
     - What is covered
   * - Page model
     - Unit
     - 21
     - Properties, custom attributes, isNew logic, collections, L18N constants
   * - Content model
     - Unit
     - 9
     - Properties, ObjectStorage collections, category operations
   * - ModifyPagesEvent
     - Unit
     - 5
     - PSR-14 contract, filtering, enrichment patterns
   * - TeaserController
     - Unit
     - 19
     - Setting helpers, special orderings, view path resolution, nesting,
       page UID resolution
   * - ItemsProcFunc
     - Unit
     - 7
     - DI fallback, FlexForm presets, edge cases
   * - Settings utility
     - Unit
     - 4
     - TypoScript rendering, fallbacks, nested arrays
   * - GetContentViewHelper
     - Unit
     - 4
     - Null handling, type/colPos filtering, index limiting, invalid entry guard
   * - RemoveWhitespacesViewHelper
     - Unit
     - 2
     - Whitespace removal, null children
   * - StripTagsViewHelper
     - Unit
     - 3
     - Tag stripping from argument and child content
   * - PageRepository
     - Functional
     - 14
     - findByPid, findByPidList, recursive queries, ordering, nav_hide


CI matrix
---------

GitHub Actions (``.github/workflows/tests.yml``) runs on every push and pull
request:

- **lint**: ``composer validate --strict`` and ``php -l`` on PHP 8.3, 8.4 and 8.5
- **cgl**: php-cs-fixer dry-run with the TYPO3 coding standards
- **phpstan**: level 8 against TYPO3 ^13.4 and ^14.3
- **unit** and **functional**: PHP 8.3/8.4 with TYPO3 ^13.4 and PHP 8.4/8.5
  with TYPO3 ^14.3 (PHP 8.5 is an allowed failure)
