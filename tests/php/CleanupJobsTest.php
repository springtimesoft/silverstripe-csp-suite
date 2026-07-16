<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\Queries\SQLSelect;
use Springtimesoft\CSPSuite\Jobs\CSPDocumentCleanupJob;
use Springtimesoft\CSPSuite\Jobs\CSPUserAgentCleanupJob;
use Springtimesoft\CSPSuite\Jobs\CSPViolationCleanupJob;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Springtimesoft\CSPSuite\Models\CSPUserAgent;
use Springtimesoft\CSPSuite\Models\CSPViolation;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;

class CleanupJobsTest extends SapphireTest
{
    protected static $fixture_file = 'CleanupJobsTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        // The fixture's "recent" violation is one hour old relative to this date,
        // keeping it inside the default P1M retention period.
        DBDatetime::set_mock_now('2026-07-15 10:00:00');
    }

    public function testSetupFindsOldViolationsAndBatches()
    {
        Config::modify()->set(CSPViolationCleanupJob::class, 'deletion_batch_size', 2);

        $job = new CSPViolationCleanupJob();
        $job->setup();

        // 3 old violations in batches of 2 = 2 steps
        $this->assertSame(2, $job->getJobData()->totalSteps);
        $this->assertFalse($job->jobFinished());
    }

    public function testProcessDeletesOldViolationsAndQueuesCleanupJobs()
    {
        Config::modify()->set(CSPViolationCleanupJob::class, 'deletion_batch_size', 2);

        $job = new CSPViolationCleanupJob();
        $job->setup();

        while (!$job->jobFinished()) {
            $job->process();
        }

        $this->assertCount(1, CSPViolation::get());
        $this->assertSame(
            'https://evil.example.com/recent.js',
            CSPViolation::get()->first()->BlockedURI
        );

        // Join rows for the deleted violations must be gone too
        foreach (['Documents', 'UserAgents'] as $relation) {
            $joinTable = DataObject::getSchema()->manyManyComponent(CSPViolation::class, $relation)['join'];
            $joinCount = SQLSelect::create('COUNT(*)', "\"{$joinTable}\"")->execute()->value();
            $this->assertSame(1, (int) $joinCount, "Only the recent violation's {$relation} join row should remain");
        }

        foreach ([CSPDocumentCleanupJob::class, CSPUserAgentCleanupJob::class] as $implementation) {
            $this->assertNotNull(
                QueuedJobDescriptor::get()->find('Implementation', $implementation),
                "A {$implementation} should be queued after cleanup"
            );
        }
    }

    public function testSetupCompletesImmediatelyWhenNothingToDelete()
    {
        // With a very long retention period nothing qualifies for deletion
        Config::modify()->set(CSPViolationCleanupJob::class, 'retention_period', 'P100Y');

        $job = new CSPViolationCleanupJob();
        $job->setup();

        $this->assertTrue($job->jobFinished());
        $this->assertCount(4, CSPViolation::get());
        foreach ([CSPDocumentCleanupJob::class, CSPUserAgentCleanupJob::class] as $implementation) {
            $this->assertNull(
                QueuedJobDescriptor::get()->find('Implementation', $implementation)
            );
        }
    }

    public function testDocumentCleanupJobDeletesOrphans()
    {
        // Orphan the old document by removing all of its violations
        foreach (['old1', 'old2', 'old3'] as $name) {
            $this->objFromFixture(CSPViolation::class, $name)->delete();
        }

        $job = new CSPDocumentCleanupJob();
        $job->process();

        $this->assertTrue($job->jobFinished());
        $this->assertCount(1, CSPDocument::get());
        $this->assertSame('/recent-page/', CSPDocument::get()->first()->URI);
    }

    public function testDocumentCleanupJobCompletesWithNoOrphans()
    {
        $job = new CSPDocumentCleanupJob();
        $job->process();

        $this->assertTrue($job->jobFinished());
        $this->assertCount(2, CSPDocument::get());
    }

    public function testUserAgentCleanupJobDeletesOrphans()
    {
        // Orphan the old user agent by removing all of its violations
        foreach (['old1', 'old2', 'old3'] as $name) {
            $this->objFromFixture(CSPViolation::class, $name)->delete();
        }

        $job = new CSPUserAgentCleanupJob();
        $job->process();

        $this->assertTrue($job->jobFinished());
        $this->assertCount(1, CSPUserAgent::get());
        $this->assertSame('Recent Browser 2.0', CSPUserAgent::get()->first()->Name);
    }

    public function testUserAgentCleanupJobCompletesWithNoOrphans()
    {
        $job = new CSPUserAgentCleanupJob();
        $job->process();

        $this->assertTrue($job->jobFinished());
        $this->assertCount(2, CSPUserAgent::get());
    }
}
