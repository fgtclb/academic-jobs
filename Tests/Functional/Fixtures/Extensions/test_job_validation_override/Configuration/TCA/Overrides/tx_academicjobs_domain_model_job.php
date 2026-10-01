<?php

declare(strict_types=1);

defined('TYPO3') or die();

// One key of a column the jobs settings configure. The settings leave the link
// optional, so this does not survive them.
$GLOBALS['TCA']['tx_academicjobs_domain_model_job']['columns']['link']['config']['required'] = true;
