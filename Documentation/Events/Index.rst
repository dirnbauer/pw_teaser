.. include:: ../Includes.txt

.. _events:


Events
======

ModifyPagesEvent
----------------

pw_teaser dispatches ``PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent`` after
the pages have been loaded and before they are passed to the view. Listeners
can filter, sort or enrich the result:

- ``getPages()`` / ``setPages(array $pages)`` – the ``Page`` models to render
- ``getTeaserController()`` – the dispatching ``TeaserController``

Register a listener with the ``AsEventListener`` attribute (TYPO3 13 and 14):

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace VendorName\YourExtension\EventListener;

   use PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent;
   use TYPO3\CMS\Core\Attribute\AsEventListener;

   #[AsEventListener(identifier: 'your-extension/modify-teaser-pages')]
   final class ModifyTeaserPagesListener
   {
       public function __invoke(ModifyPagesEvent $event): void
       {
           $pages = $event->getPages();
           // filter, sort or add data ...
           $event->setPages(array_reverse($pages));
       }
   }

Alternatively register the listener in your ``Configuration/Services.yaml``:

.. code-block:: yaml

   services:
     VendorName\YourExtension\EventListener\ModifyTeaserPagesListener:
       tags:
         - name: event.listener
           identifier: 'your-extension/modify-teaser-pages'
           event: PwTeaserTeam\PwTeaser\Event\ModifyPagesEvent
