<?php

namespace Springtimesoft\CSPSuite\Jobs;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 * Performs mass deletion of CSP user agents that have no associated violations.
 *
 * This job is automatically queued after a CSPViolationCleanupJob.
 */
class CSPUserAgentCleanupJob extends AbstractQueuedJob
{
    public function getTitle()
    {
        return 'CSP User Agent Cleanup Job';
    }

    public function process()
    {
        $orphanedUserAgentIDs    = CSPUserAgent::get()->filter('CSPViolations.Count()', 0)->column('ID');
        $orphanedUserAgentIDList = implode(', ', $orphanedUserAgentIDs);

        if (empty($orphanedUserAgentIDs)) {
            $this->addMessage('No orphaned user agents to delete.');
            $this->isComplete = true;

            return;
        }

        $cspUserAgentTable = DataObject::getSchema()->baseDataTable(CSPUserAgent::class);
        SQLDelete::create("\"{$cspUserAgentTable}\"")->addWhere(sprintf('"ID" IN (%s)', $orphanedUserAgentIDList))->execute();

        $this->addMessage('Deleted ' . count($orphanedUserAgentIDs) . ' orphaned user agent(s).');

        $this->isComplete = true;
    }
}
