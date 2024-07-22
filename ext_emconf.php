<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Jobs',
    'description' => 'The Academic Jobs extension allows users to create and manage job postings.',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-12.4.99',
        ],
    ],
    'state' => 'beta',
    'version' => '0.1.6',
    'clearCacheOnLoad' => true,
    'category' => 'fe,be',
    'author' => 'Riad Zejnilagic Trumic',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'autoload' => [
        'psr-4' => [
            'Fgtclb\\AcademicJobs\\' => 'Classes/',
        ],
    ],
];
