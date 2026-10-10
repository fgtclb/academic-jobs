<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Upgrades;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Repairs what the new job form stored before it kept the values a visitor chose
 * (ACE-864), as far as the stored values tell.
 *
 * **Start date and application deadline.** The form parsed both dates with the format
 * `Y-m-d`, which takes the missing time of day from the current time, so a job was
 * stored with the time of day of its submission in `starttime` and in `endtime`, and
 * disappeared in the afternoon of its deadline day. Both fields are `datetime` in the
 * backend, an editor may set a time of day on purpose, and nothing marks a job as made
 * by the form. A job is therefore recognised by a fingerprint: both fields are set and
 * carry the same time of day down to the second, other than midnight, in the time zone
 * of the server. Such a job gets what the form stores today: the start date at
 * midnight of its day, the deadline at the last second of its day.
 *
 * A job with only one of the two dates cannot be recognised. Its time of day is the
 * submission time or the choice of an editor, and the one value does not tell which.
 *
 * **Employment type and job type.** A `0` in `employment_type` or `type` is what the
 * form stored for a select the visitor left on "Please choose". The value the visitor
 * meant cannot be derived, so these jobs are listed through the wizard output, with
 * uid, page and title, for an editor to choose in the backend. Nothing of them is
 * changed.
 *
 * **Every row counts** apart from deleted ones: hidden, scheduled and expired jobs,
 * translations and workspace versions. Both date fields have no `l10n_mode`, so a
 * translation carries values of its own and is judged by them. A workspace version is
 * repaired like a live job, otherwise publishing it would bring the old values back.
 * A delete placeholder of a workspace is left out, its values are not used again.
 *
 * Every repaired row is written on its own through the connection, and only if both
 * values are still the ones that were read. The wizard writes the database directly,
 * not through the DataHandler: both fields are plain integers without a relation or a
 * reference index entry to maintain. The change is therefore not recorded in the
 * history of the record.
 *
 * The wizard is **not** repeatable. It reports itself as necessary while a job carries
 * the fingerprint or a `0`, lists the jobs with a `0` once, in the run an integrator
 * reads, and the core then marks it as done. A repeatable wizard would stay necessary as
 * long as one of those jobs is left, an expired one nobody edits included, and the
 * reports module would show an incomplete update for good. Run again anyway, through
 * `upgrade:mark:undone`, it changes no date, a repaired job no longer has the same time
 * of day on both fields.
 *
 * The time zone is the one of the process: `phpTimeZone`, or the default time zone of
 * PHP when that is empty, which can differ between the command line and the web server
 * the form ran in. The description names it, so it is visible before anything is
 * written, and nothing records the old values.
 *
 * The output is set by the core through {@see ChattyInterface::setOutput()} before the
 * wizard runs. That setter is the one piece of state of this class, the interface
 * leaves no other way to receive it, so the service is not shared: every retrieval
 * gets an instance of its own, and an output set for one run does not reach the next.
 */
#[Autoconfigure(shared: false)]
#[UpgradeWizard('academicJobs_repairNewJobFormValues')]
final class RepairNewJobFormValuesUpgradeWizard implements UpgradeWizardInterface, ChattyInterface
{
    private const TABLE = 'tx_academicjobs_domain_model_job';

    private OutputInterface $output;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
        $this->output = new NullOutput();
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getTitle(): string
    {
        return 'Repair the jobs stored by the old new job form of academic jobs';
    }

    public function getDescription(): string
    {
        return 'Before the new job form stored whole days, it stored the start date and the application'
            . ' deadline with the time of day of the submission, so a job disappeared in the afternoon of'
            . ' its deadline day. A job whose start date and deadline are both set and carry the same time'
            . ' of day, other than midnight, gets its start date moved to midnight and its deadline to the'
            . ' last second of its day. A job with another time of day on either field, or with only one of'
            . ' the two dates, is left as it is. Jobs without a chosen "Employment Type" or "Job Type" are'
            . ' listed once for an editor, the right value cannot be derived. Translations, hidden and'
            . ' expired jobs and workspace versions are included. The time of day is read and written in'
            . ' the time zone ' . date_default_timezone_get() . ', the one of this process. It has to be'
            . ' the zone the form ran in, the old values are not kept.';
    }

    public function updateNecessary(): bool
    {
        return $this->findJobsWithSubmissionTimes() !== [] || $this->findJobsWithoutChosenValues() !== [];
    }

    public function executeUpdate(): bool
    {
        $timeZone = new \DateTimeZone(date_default_timezone_get());
        $repaired = 0;
        foreach ($this->findJobsWithSubmissionTimes() as $job) {
            $repaired += $this->repairDates($job, $timeZone);
        }
        $this->output->writeln(sprintf(
            'Moved the start date and the application deadline of %d job record(s) to whole days in the time zone %s.',
            $repaired,
            $timeZone->getName(),
        ));

        $jobsWithoutChosenValues = $this->findJobsWithoutChosenValues();
        if ($jobsWithoutChosenValues !== []) {
            $this->output->writeln(sprintf(
                '%d job record(s) have no "Employment Type" or "Job Type" chosen. Choose them in the backend:',
                count($jobsWithoutChosenValues),
            ));
            foreach ($jobsWithoutChosenValues as $job) {
                $this->output->writeln($this->describeJobWithoutChosenValues($job));
            }
        }
        return true;
    }

    /**
     * @return list<class-string>
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Every job with both dates set whose time of day is the same on both and is not
     * midnight. The time of day depends on the time zone, which SQL cannot apply on
     * every database system, so the candidates are compared here.
     *
     * @return list<array{uid: int, starttime: int, endtime: int}>
     */
    private function findJobsWithSubmissionTimes(): array
    {
        $timeZone = new \DateTimeZone(date_default_timezone_get());
        $queryBuilder = $this->createQueryBuilder();
        $result = $queryBuilder
            ->select('uid', 'starttime', 'endtime')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('starttime', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('endtime', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $this->excludeDeletePlaceholders($queryBuilder),
            )
            ->orderBy('uid')
            ->executeQuery();

        $jobs = [];
        while ($row = $result->fetchAssociative()) {
            $starttime = (int)$row['starttime'];
            $endtime = (int)$row['endtime'];
            $timeOfDay = $this->timeOfDay($starttime, $timeZone);
            if ($timeOfDay === '00:00:00' || $timeOfDay !== $this->timeOfDay($endtime, $timeZone)) {
                continue;
            }
            $jobs[] = [
                'uid' => (int)$row['uid'],
                'starttime' => $starttime,
                'endtime' => $endtime,
            ];
        }
        return $jobs;
    }

    /**
     * @return list<array{uid: int, pid: int, title: string, languageUid: int, workspaceUid: int, employmentType: int, type: int}>
     */
    private function findJobsWithoutChosenValues(): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $result = $queryBuilder
            ->select('uid', 'pid', 'title', 'sys_language_uid', 't3ver_wsid', 'employment_type', 'type')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('employment_type', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                ),
                $this->excludeDeletePlaceholders($queryBuilder),
            )
            ->orderBy('uid')
            ->executeQuery();

        $jobs = [];
        while ($row = $result->fetchAssociative()) {
            $jobs[] = [
                'uid' => (int)$row['uid'],
                'pid' => (int)$row['pid'],
                'title' => (string)$row['title'],
                'languageUid' => (int)$row['sys_language_uid'],
                'workspaceUid' => (int)$row['t3ver_wsid'],
                'employmentType' => (int)$row['employment_type'],
                'type' => (int)$row['type'],
            ];
        }
        return $jobs;
    }

    /**
     * Only the deleted restriction: hidden, scheduled and expired jobs are exactly the
     * ones an old deadline may already have hidden, and workspace rows are wanted too.
     */
    private function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        return $queryBuilder;
    }

    private function excludeDeletePlaceholders(QueryBuilder $queryBuilder): string
    {
        return (string)$queryBuilder->expr()->neq(
            't3ver_state',
            $queryBuilder->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT),
        );
    }

    /**
     * The start date gets midnight of its day and the deadline the last second of its
     * day, computed as the new job form computes them: the day parsed with `!Y-m-d` in
     * the time zone of the server, the deadline then set to 23:59:59.
     *
     * The row is written only if it still holds the values that were read, so a job an
     * editor saved in between is not overwritten.
     *
     * @param array{uid: int, starttime: int, endtime: int} $job
     */
    private function repairDates(array $job, \DateTimeZone $timeZone): int
    {
        $starttime = $this->startOfDay($job['starttime'], $timeZone);
        $endtime = $this->startOfDay($job['endtime'], $timeZone)->setTime(23, 59, 59);

        return $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            [
                'starttime' => $starttime->getTimestamp(),
                'endtime' => $endtime->getTimestamp(),
            ],
            [
                'uid' => $job['uid'],
                'starttime' => $job['starttime'],
                'endtime' => $job['endtime'],
            ],
            [
                'starttime' => Connection::PARAM_INT,
                'endtime' => Connection::PARAM_INT,
                'uid' => Connection::PARAM_INT,
            ],
        );
    }

    private function startOfDay(int $timestamp, \DateTimeZone $timeZone): \DateTimeImmutable
    {
        $day = (new \DateTimeImmutable('@' . $timestamp))->setTimezone($timeZone)->format('Y-m-d');
        $startOfDay = \DateTimeImmutable::createFromFormat('!Y-m-d', $day, $timeZone);
        if ($startOfDay === false) {
            throw new \RuntimeException(sprintf('The day "%s" could not be parsed.', $day), 1791663300);
        }
        return $startOfDay;
    }

    private function timeOfDay(int $timestamp, \DateTimeZone $timeZone): string
    {
        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone($timeZone)->format('H:i:s');
    }

    /**
     * @param array{uid: int, pid: int, title: string, languageUid: int, workspaceUid: int, employmentType: int, type: int} $job
     */
    private function describeJobWithoutChosenValues(array $job): string
    {
        $missing = [];
        if ($job['employmentType'] === 0) {
            $missing[] = 'no Employment Type';
        }
        if ($job['type'] === 0) {
            $missing[] = 'no Job Type';
        }
        $location = sprintf('uid %d, page %d', $job['uid'], $job['pid']);
        if ($job['languageUid'] !== 0) {
            $location .= sprintf(', language %d', $job['languageUid']);
        }
        if ($job['workspaceUid'] !== 0) {
            $location .= sprintf(', workspace %d', $job['workspaceUid']);
        }
        return sprintf('  - %s: "%s" (%s)', $location, $job['title'], implode(', ', $missing));
    }
}
