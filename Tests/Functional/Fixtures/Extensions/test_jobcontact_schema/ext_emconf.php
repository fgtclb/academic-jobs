<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs TCA',
    'description' => 'Extension providing TCA for tests',
    'category' => 'fe,be',
    'author' => 'Stefan Bürk',
    'author_email' => 'stefan@buerk.tech',
    'author_company' => 'web-vision GmbH',
    'state' => 'beta',
    'version' => '2.1.0',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_jobs' => '2.1.0',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
