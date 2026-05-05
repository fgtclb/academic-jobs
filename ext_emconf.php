<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Jobs',
    'description' => 'The Academic Jobs extension allows users to create and manage job postings.',
    'version' => '2.3.2',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'rte-ckeditor' => '12.4.22-13.4.99',
            'install' => '12.4.22-13.4.99',
            'fluid-styled-content' => '12.4.22-13.4.99',
            'academic_base' => '2.3.2',
        ],
    ],
];
