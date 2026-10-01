<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs validation settings override',
    'description' => 'A site package that overrides the jobs validation settings and the job TCA, for the functional tests of the settings',
    'version' => '3.0.0',
    'category' => 'plugin',
    'state' => 'beta',
    'author' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_jobs' => '3.0.0',
        ],
    ],
];
