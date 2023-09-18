<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Academic Jobs',
    'description' => 'The AcademicJobs extension allows users to create and manage job postings.',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
    'author' => 'web-vision Team',
    'author_email' => 'hello@web-vision.de',
    'autoload' => [
        'psr-4' => [
            'Fgtclb\\AcademicJobs\\' => 'Classes/',
        ],
    ],
];
