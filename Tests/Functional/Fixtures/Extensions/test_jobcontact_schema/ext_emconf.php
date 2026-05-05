<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs TCA',
    'description' => 'Extension providing TCA for tests',
    'version' => '2.3.4',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_jobs' => '2.3.4',
        ],
    ],
];
