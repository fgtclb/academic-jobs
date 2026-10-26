<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Jobs select items listener',
    'description' => 'Extension listening to the select items event of academic_base for the job form of academic_jobs',
    'version' => '2.4.1',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_jobs' => '2.4.1',
        ],
    ],
];
