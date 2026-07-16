<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Springtimesoft\CSPSuite\Models\CSPUserAgent;
use Springtimesoft\CSPSuite\Models\CSPViolation;

class CSPViolationsControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'CSPViolationsControllerTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        $this->objFromFixture(SiteTree::class, 'home')->publishRecursive();

        DBDatetime::set_mock_now('2026-07-15 10:00:00');
    }

    protected function tearDown(): void
    {
        DBDatetime::clear_mock_now();

        parent::tearDown();
    }

    public function testReportUriIngestCreatesViolation()
    {
        $response = $this->postReport($this->getReportUriBody());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, CSPViolation::get());

        $violation = CSPViolation::get()->first();
        $this->assertSame(1, (int) $violation->Violations);
        $this->assertSame('enforce', $violation->Disposition);
        $this->assertSame('https://evil.example.com/bad.js', $violation->BlockedURI);
        $this->assertSame('script-src', $violation->EffectiveDirective);
        // Seconds are stripped from the reported time before it is stored
        $this->assertSame('2026-07-15 10:00:00', $violation->ReportedTime);

        $this->assertCount(1, $violation->Documents());
        $this->assertSame('/some-page/', $violation->Documents()->first()->URI);
        $this->assertCount(1, $violation->UserAgents());
    }

    public function testDuplicateReportIncrementsExistingViolation()
    {
        $this->postReport($this->getReportUriBody());
        $this->postReport($this->getReportUriBody());

        $this->assertCount(1, CSPViolation::get());

        $violation = CSPViolation::get()->first();
        $this->assertSame(2, (int) $violation->Violations);
        $this->assertCount(1, $violation->Documents());
        $this->assertCount(1, CSPDocument::get());
    }

    public function testReportToIngest()
    {
        $body = json_encode([
            [
                'type' => 'csp-violation',
                'age'  => 5000,
                'body' => [
                    'blockedURL'         => 'https://evil.example.com/tracker.png',
                    'documentURL'        => '/some-page/',
                    'effectiveDirective' => 'img-src',
                    'disposition'        => 'report',
                    'sourceFile'         => 'https://example.com/script.js',
                ],
            ],
            [
                // Non-CSP report types in the same payload must be ignored
                'type' => 'deprecation',
                'age'  => 5000,
                'body' => [],
            ],
        ]);

        $response = $this->postReport($body, 'application/reports+json');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, CSPViolation::get());

        $violation = CSPViolation::get()->first();
        $this->assertSame('report', $violation->Disposition);
        $this->assertSame('https://evil.example.com/tracker.png', $violation->BlockedURI);
        $this->assertSame('img-src', $violation->EffectiveDirective);
        $this->assertSame('https://example.com/script.js', $violation->SourceFile);
        // 'age' (5000ms) is subtracted from the mocked clock and seconds are stripped
        $this->assertSame('2026-07-15 09:59:00', $violation->ReportedTime);
    }

    public function testFirefoxLegacyReportFallbacks()
    {
        // Firefox omits disposition and effective-directive from report-uri reports
        $body = json_encode([
            'csp-report' => [
                'blocked-uri'        => 'https://evil.example.com/bad.css',
                'document-uri'       => '/some-page/',
                'violated-directive' => 'style-src',
            ],
        ]);

        $response = $this->postReport($body);

        $this->assertSame(200, $response->getStatusCode());

        $violation = CSPViolation::get()->first();
        $this->assertNotNull($violation);
        $this->assertSame('unknown', $violation->Disposition);
        $this->assertSame('style-src', $violation->EffectiveDirective);
    }

    public function testInvalidContentTypeRejected()
    {
        $response = $this->postReport($this->getReportUriBody(), 'text/html');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertCount(0, CSPViolation::get());
    }

    public function testCrossOriginRejected()
    {
        $response = $this->postReport($this->getReportUriBody(), 'application/csp-report', [
            'Origin' => 'https://attacker.example.com',
        ]);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertCount(0, CSPViolation::get());
    }

    public function testSameOriginAccepted()
    {
        $response = $this->postReport($this->getReportUriBody(), 'application/csp-report', [
            'Origin' => Director::protocolAndHost(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, CSPViolation::get());
    }

    public function testDocumentLinksToSiteTree()
    {
        $page = $this->objFromFixture(SiteTree::class, 'home');

        $body = json_encode([
            'csp-report' => [
                'blocked-uri'         => 'https://evil.example.com/bad.js',
                'document-uri'        => $page->Link(),
                'effective-directive' => 'script-src',
                'disposition'         => 'enforce',
            ],
        ]);

        $this->postReport($body);

        $document = CSPDocument::get()->first();
        $this->assertNotNull($document);
        $this->assertSame($page->ID, (int) $document->SiteTreeID);
    }

    public function testUserAgentParsedToHumanLabel()
    {
        $chromeUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            . ' (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

        $this->postReport($this->getReportUriBody(), 'application/csp-report', [
            'User-Agent' => $chromeUA,
        ]);

        $userAgent = CSPUserAgent::get()->find('Raw', $chromeUA);
        $this->assertNotNull($userAgent);
        $this->assertStringContainsString('Chrome', $userAgent->Name);
    }

    private function getReportUriBody(): string
    {
        return json_encode([
            'csp-report' => [
                'blocked-uri'         => 'https://evil.example.com/bad.js',
                'document-uri'        => '/some-page/',
                'effective-directive' => 'script-src',
                'disposition'         => 'enforce',
            ],
        ]);
    }

    private function postReport(
        string $body,
        string $contentType = 'application/csp-report',
        array $headers = []
    ): HTTPResponse {
        return $this->post(
            'csp-violations',
            [],
            array_merge(['Content-Type' => $contentType], $headers),
            null,
            $body
        );
    }
}
