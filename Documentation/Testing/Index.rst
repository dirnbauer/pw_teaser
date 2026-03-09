.. include:: ../Includes.txt

.. _testing:


Testing
=======

pw_teaser currently ships with **77 unit test cases** (74 unit test methods)
and **14 functional tests**.


Run tests locally
-----------------

Start the local environment first:

.. code-block:: bash

   ddev start

Run unit tests:

.. code-block:: bash

   ddev exec vendor/bin/phpunit -c Tests/UnitTests.xml

Run functional tests (with TYPO3 testing framework DB variables):

.. code-block:: bash

   ddev exec bash -c 'typo3DatabaseName=db typo3DatabaseHost=db \
     typo3DatabaseUsername=db typo3DatabasePassword=db \
     typo3DatabaseDriver=mysqli \
     vendor/bin/phpunit -c Tests/FunctionalTests.xml'

Run static analysis:

.. code-block:: bash

   ddev exec php vendor/bin/phpstan analyse -c phpstan.neon


Functional test database grants
-------------------------------

If you get errors like ``Access denied ... to database db_ft...`` while running
functional tests, ensure the ``db`` user may create TYPO3 test databases:

.. code-block:: bash

   ddev mysql -e "GRANT ALL ON \`db_%\`.* TO 'db'@'%'; FLUSH PRIVILEGES;"


CI matrix
---------

GitHub Actions validates:

- Unit tests on PHP 8.2/8.3/8.4 with TYPO3 13.4 and 14.0
- Functional tests on PHP 8.2 + TYPO3 13.4 and PHP 8.3 + TYPO3 14.0
- PHPStan (level 9)
- Composer validation
