<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use Springtimesoft\CSPSuite\GridFieldComponents\ClearAllCSPViolationsAction;
use Springtimesoft\CSPSuite\Models\CSPViolation;
use Springtimesoft\CSPSuite\Reports\CSPViolationsReport;

class CSPViolationsReportTest extends SapphireTest
{
    protected static $fixture_file = 'CSPViolationTest.yml';

    public function testSourceRecords()
    {
        $records = (new CSPViolationsReport())->sourceRecords();

        $this->assertCount(1, $records);
        $this->assertSame(
            $this->objFromFixture(CSPViolation::class, 'violation1')->ID,
            $records->first()->ID
        );
    }

    public function testTitleAndDescription()
    {
        $report = new CSPViolationsReport();

        $this->assertNotEmpty($report->title());
        $this->assertStringContainsString('font-icon-external-link', $report->description());
    }

    public function testReportFieldConfiguration()
    {
        $this->logInWithPermission('ADMIN');

        $gridConfig = (new CSPViolationsReport())->getReportField()->getConfig();

        $this->assertNotNull($gridConfig->getComponentByType(ClearAllCSPViolationsAction::class));
        $this->assertNotNull($gridConfig->getComponentByType(GridFieldDeleteAction::class));

        /** @var GridFieldExportButton $exportButton */
        $exportButton  = $gridConfig->getComponentByType(GridFieldExportButton::class);
        $exportColumns = $exportButton->getExportColumns();

        $this->assertContains('DocumentURIList', $exportColumns);
        $this->assertContains('UserAgentList', $exportColumns);
    }
}
