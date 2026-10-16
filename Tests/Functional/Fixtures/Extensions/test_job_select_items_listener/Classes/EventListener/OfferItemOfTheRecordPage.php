<?php

declare(strict_types=1);

namespace TESTS\TestJobSelectItemsListener\EventListener;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Adds an item naming the page of the record to both selects of the job record, the way
 * a project listener decides by the page of the record. It reads `row` itself without a
 * fallback and hands it on to a method typed `array`, which a listener written against
 * the former shape of the event, with `row` always present, could do. It reads the
 * fields of the row with a fallback, so the page of a record that is not stored yet is
 * `effectivePid`. That is the narrower case the empty row of the job form protects:
 * before the form passed it, the listener raised a warning and a `TypeError` there. A
 * listener that reads `$parameters['row']['pid']` without a fallback still warns.
 */
final class OfferItemOfTheRecordPage
{
    #[AsEventListener(identifier: 'test-job-select-items-listener/offer-item-of-the-record-page')]
    public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
    {
        $parameters = $event->getParameters();
        if ($parameters['table'] !== 'tx_academicjobs_domain_model_job'
            || !in_array($parameters['field'], ['type', 'employment_type'], true)
        ) {
            return;
        }
        // The shape declares the row optional now, and this is the report a listener
        // written against the former shape gets.
        // @phpstan-ignore offsetAccess.notFound
        $row = $parameters['row'];
        $parameters['items'][] = [
            'label' => sprintf('Offered on page %d', $this->recordPage($row, $parameters['effectivePid'])),
            'value' => 99,
        ];
        $event->setParameters($parameters);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function recordPage(array $row, int $effectivePid): int
    {
        return (int)($row['pid'] ?? $effectivePid);
    }
}
