<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use Springtimesoft\CSPSuite\Models\CSPViolation;

class CSPViolationTest extends SapphireTest
{
    protected static $fixture_file = 'CSPViolationTest.yml';

    public function testGetDocumentURIList()
    {
        $violation = $this->objFromFixture(CSPViolation::class, 'violation1');

        $this->assertSame(
            '/page-1/, /page-2/, /page-3/',
            $violation->getDocumentURIList()->getValue()
        );
    }

    public function testGetDocumentURISummaryTruncates()
    {
        Config::modify()->set(CSPViolation::class, 'uri_summary_limit', 2);

        $violation = $this->objFromFixture(CSPViolation::class, 'violation1');

        $this->assertSame(
            '/page-1/, /page-2/, and 1 more',
            $violation->getDocumentURISummary()->getValue()
        );
    }

    public function testGetDocumentURISummaryNoTruncationUnderLimit()
    {
        $violation = $this->objFromFixture(CSPViolation::class, 'violation1');

        $this->assertSame(
            '/page-1/, /page-2/, /page-3/',
            $violation->getDocumentURISummary()->getValue()
        );
    }

    public function testGetUserAgentListIncludesRawUserAgents()
    {
        $violation = $this->objFromFixture(CSPViolation::class, 'violation1');

        $this->assertSame(
            'Chrome 126.0.0 (Windows 10) (chrome-raw-ua),'
                . ' Firefox 128.0 (Ubuntu) (firefox-raw-ua),'
                . ' Safari 17.5 (Mac OS X 14.5) (safari-raw-ua)',
            $violation->getUserAgentList()->getValue()
        );
    }

    public function testGetUserAgentSummaryTruncates()
    {
        Config::modify()->set(CSPViolation::class, 'user_agent_summary_limit', 1);

        $violation = $this->objFromFixture(CSPViolation::class, 'violation1');

        $this->assertSame(
            '<span title="chrome-raw-ua">Chrome 126.0.0 (Windows 10)</span>, and 2 more',
            $violation->getUserAgentSummary()->getValue()
        );
    }

    public function testGetUserAgentSummaryNoTruncationUnderLimit()
    {
        $violation = $this->objFromFixture(CSPViolation::class, 'violation1');

        $this->assertSame(
            '<span title="chrome-raw-ua">Chrome 126.0.0 (Windows 10)</span>,'
                . ' <span title="firefox-raw-ua">Firefox 128.0 (Ubuntu)</span>,'
                . ' <span title="safari-raw-ua">Safari 17.5 (Mac OS X 14.5)</span>',
            $violation->getUserAgentSummary()->getValue()
        );
    }
}
