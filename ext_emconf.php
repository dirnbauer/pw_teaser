<?php
// phpcs:disable
$EM_CONF[$_EXTKEY] = [
    'title' => 'Page Teaser (with Fluid)',
    'description' => 'Create powerful page teasers in TYPO3 CMS with data from page properties and its content elements. Based on Extbase and Fluid template engine.',
    'category' => 'plugin',
    'version' => '6.0.3',
    'state' => 'stable',
    'author' => 'Armin Vieweg',
    'author_email' => 'info@v.ieweg.de',
    'author_company' => 'v.ieweg Webentwicklung',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.4.99',
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => ['PwTeaserTeam\\PwTeaser\\' => 'Classes']
    ],
];
