<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Jobs',
    'description' => 'The Academic Jobs extension allows users to create and manage job postings.',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
    'state' => 'beta',
    'version' => '1.1.5',
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
