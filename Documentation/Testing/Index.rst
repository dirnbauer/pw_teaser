.. _testing:


Testing and development
=======================

.. contents:: :local:

Unit tests live in ``Tests/Unit``, functional tests in ``Tests/Functional``;
their PHPUnit configurations are ``Build/phpunit/UnitTests.xml`` and
``Build/phpunit/FunctionalTests.xml``. The Composer scripts below wrap the
tools that CI runs.


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


What is covered
---------------

Unit tests cover the domain models (properties, custom attributes,
``isNew``, collections, the raw-row accessor), ``ModifyPagesEvent``, the
controller orchestration (special orderings, nesting, pagination, page uid
resolution), the ``ItemsProcFunc`` preset dropdown, the ViewHelpers and the
settings and view layer: ``TeaserSettings``, ``TeaserSource``,
``SettingsRenderer``, ``CategoryMode`` and ``TemplateConfiguration``.

Functional tests exercise the database layer against a real TYPO3 instance:
``PageRepository`` (children, recursive descendants, hand-picked uids,
ordering, limits, ``nav_hide``, doktypes, ignored uids, all four category
modes, ``l18n_cfg`` translation visibility and language overlays),
``ContentRepository`` and ``RecordRowLoader``.


CI matrix
---------

GitHub Actions (``.github/workflows/ci.yml``) runs on every push and pull
request:

- **lint**: ``composer validate --strict`` and ``php -l`` on PHP 8.3, 8.4 and 8.5
- **cgl**: php-cs-fixer dry-run with the TYPO3 coding standards
- **phpstan**: level 8 against TYPO3 ^13.4 and ^14.3
- **unit** and **functional**: PHP 8.3/8.4 with TYPO3 ^13.4 and PHP 8.4/8.5
  with TYPO3 ^14.3 (PHP 8.5 is an allowed failure). Functional tests run
  against MariaDB 10.11.
