<?php

namespace Springtimesoft\CSPSuite\Jobs;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 * Performs mass deletion of CSP documents that have no associated violations.
 *
 * This job is automatically queued after a CSPViolationCleanupJob.
 */
class CSPDocumentCleanupJob extends AbstractQueuedJob
{
    public function getTitle()
    {
        return 'CSP Document Cleanup Job';
    }

    public function process()
    {
        $orphanedDocumentIDs    = CSPDocument::get()->filter('CSPViolations.Count()', 0)->column('ID');
        $orphanedDocumentIDList = implode(', ', $orphanedDocumentIDs);

        if (empty($orphanedDocumentIDs)) {
            $this->addMessage('No orphaned documents to delete.');
            $this->isComplete = true;

            return;
        }

        $cspDocumentTable = DataObject::getSchema()->baseDataTable(CSPDocument::class);
        SQLDelete::create("\"{$cspDocumentTable}\"")->addWhere(sprintf('"ID" IN (%s)', $orphanedDocumentIDList))->execute();

        $this->addMessage('Deleted ' . count($orphanedDocumentIDs) . ' orphaned document(s).');

        $this->isComplete = true;
    }
}
