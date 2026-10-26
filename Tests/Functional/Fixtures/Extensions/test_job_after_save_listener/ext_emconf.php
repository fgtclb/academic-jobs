<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs After-Save Listener',
    'description' => 'Extension listening to the after-save event of the new job form for tests',
    'version' => '2.5.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_jobs' => '2.5.0',
        ],
    ],
];
