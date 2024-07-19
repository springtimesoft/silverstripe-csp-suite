<?php

namespace Springtimesoft\CSPSuite\Jobs;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use Springtimesoft\CSPSuite\Models\CSPViolation;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

class CSPViolationCleanupJob extends AbstractQueuedJob
{
    /**
     * Violations that were last reported longer than this period ago can be deleted.
     *
     * The string must be in DateInterval duration format.
     *
     * @see https://www.php.net/manual/en/dateinterval.construct.php
     */
    private static $retention_period = 'P1M';

    /**
     * Each step of the job will delete a maximum of this many violations to preserve performance.
     */
    private static $deletion_batch_size = 100;

    public function getTitle()
    {
        return 'CSP Violation Cleanup Job';
    }

    public function setup()
    {
        $this->addMessage('Starting cleanup.');

        $retention = Config::inst()->get(self::class, 'retention_period');
        $retention = new \DateInterval($retention);

        $date = new \DateTime();
        $date->sub($retention);

        $ageLimit = $date->format(\DateTime::ATOM);

        $this->addMessage("Looking for violations older than {$ageLimit}.");

        // Pull IDs for violations that are older than the retention period
        $violationsToDelete = CSPViolation::get()
            ->filter('ReportedTime:LessThan', $ageLimit)->column('ID');

        if (empty($violationsToDelete)) {
            $this->addMessage('No violations to delete.');
            $this->isComplete = true;

            return;
        }

        $this->addMessage('Found ' . count($violationsToDelete) . ' violation(s) to delete.');

        // Split the violations into batches to delete
        $this->violationsToDelete = array_chunk($violationsToDelete, Config::inst()->get(self::class, 'deletion_batch_size'));

        $this->totalSteps = count($this->violationsToDelete);
    }

    public function process()
    {
        $allViolationsToDelete = $this->violationsToDelete;
        $violationsToDelete    = array_shift($allViolationsToDelete);
        $violationIDList       = implode(', ', $violationsToDelete);

        // Perform mass deletion on targeted violations
        $cspViolationTable = DataObject::getSchema()->baseDataTable(CSPViolation::class);
        SQLDelete::create("\"{$cspViolationTable}\"")->addWhere(sprintf('"ID" IN (%s)', $violationIDList))->execute();

        // Also delete any join records for the violations
        $cspViolationDocumentJoin      = DataObject::getSchema()->manyManyComponent(CSPViolation::class, 'Documents');
        $cspViolationDocumentJoinTable = $cspViolationDocumentJoin['join'];
        SQLDelete::create("\"{$cspViolationDocumentJoinTable}\"")->addWhere(sprintf('"CSPViolationID" IN (%s)', $violationIDList))->execute();

        // If we have more violations to delete, increment the step and continue
        if (count($allViolationsToDelete) > 0) {
            $this->violationsToDelete = $allViolationsToDelete;
            ++$this->currentStep;

            return;
        }

        // Queue CSPDocumentCleanupJob to clean up any orphaned documents
        QueuedJobService::singleton()->queueJob(new CSPDocumentCleanupJob());
        $this->addMessage('Clean up complete. Queued CSPDocumentCleanupJob.');

        $this->isComplete = true;
    }
}
