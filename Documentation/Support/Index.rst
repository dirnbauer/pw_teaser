.. include:: ../Includes.txt

.. _support:


Support
=======

Issue tracker
-------------
See all open tasks `here`_ in the Github issue tracker.

.. _here: https://github.com/a-r-m-i-n/pw_teaser/issues


Donate
------
If you like the pw_teaser extension, feel free to `donate`_ some funds to support further development.

.. _donate: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2DCCULSKFRZFU


Contribute
----------
If you are a developer and you want to submit improvements as code, you can fork https://github.com/a-r-m-i-n/pw_teaser
and make a pull request to pw_teaser's master branch.

Thanks!


Testing
-------

pw_teaser ships with 73 unit tests and 14 functional tests covering models,
controllers, ViewHelpers, events, repositories, and the FlexForm user function.

**Running tests locally with DDEV:**

.. code-block:: bash

   ddev start

   # Unit tests
   ddev exec vendor/bin/phpunit -c Tests/UnitTests.xml

   # Functional tests
   ddev exec bash -c 'typo3DatabaseName=db typo3DatabaseHost=db \
     typo3DatabaseUsername=db typo3DatabasePassword=db \
     typo3DatabaseDriver=mysqli \
     vendor/bin/phpunit -c Tests/FunctionalTests.xml'

   # PHPStan (level 9)
   ddev exec php vendor/bin/phpstan analyse -c phpstan.neon

**CI pipeline:**

GitHub Actions runs unit tests across PHP 8.2/8.3/8.4 against TYPO3 13.4 and
14.0, functional tests against both LTS versions, PHPStan at level 9, and
Composer validation on every push and pull request.
