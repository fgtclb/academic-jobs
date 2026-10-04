<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs icon replacement',
    'description' => 'A site package that replaces job icons, two in the right file and two in the wrong one, for the functional tests of the job icons',
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
