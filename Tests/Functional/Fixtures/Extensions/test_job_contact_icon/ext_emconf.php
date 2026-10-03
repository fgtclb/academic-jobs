<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs contact icon replacement',
    'description' => 'A site package that replaces the phone icon of the job contact block, for the functional tests of the contact icons',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.35-14.3.99',
            'core' => '13.4.35-14.3.99',
            'academic_jobs' => '3.0.0',
        ],
    ],
];
