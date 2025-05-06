<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Jobs',
    'description' => 'The Academic Jobs extension allows users to create and manage job postings.',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
    ],
    'state' => 'beta',
    'version' => '2.0.2',
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
