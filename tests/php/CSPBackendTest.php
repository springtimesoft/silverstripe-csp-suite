<?php

namespace Springtimesoft\CSPSuite\Tests;

use Silverstripe\CSP\CSPMiddleware;
use Silverstripe\CSP\NonceGenerator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use Springtimesoft\CSPSuite\Requirements\CSPBackend;

/**
 * Covers this module's override of the CSP backend: nonces on external file tags.
 * Inline script/style nonce behaviour is already covered by silverstripeltd/silverstripe-csp.
 */
class CSPBackendTest extends SapphireTest
{
    protected $usesDatabase = false;

    private const TEMPLATE = '<head></head><body></body>';

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(CSPMiddleware::class, 'sri_enabled', false);
    }

    public function testExternalScriptGetsNonce()
    {
        $nonce = NonceGenerator::get();

        $backend = CSPBackend::create();
        $backend->javascript('springtimesoft/silverstripe-csp-suite: tests/php/example.js');

        $html = $backend->includeInHTML(self::TEMPLATE);

        $script = $this->getFirstElement($html, 'script');
        $this->assertStringContainsString('example.js', $script->getAttribute('src'));
        $this->assertSame($nonce, $script->getAttribute('nonce'));
    }

    public function testCssLinkGetsNonce()
    {
        $nonce = NonceGenerator::get();

        $backend = CSPBackend::create();
        $backend->css('springtimesoft/silverstripe-csp-suite: tests/php/example.css');

        $html = $backend->includeInHTML(self::TEMPLATE);

        $link = $this->getFirstElement($html, 'link');
        $this->assertStringContainsString('example.css', $link->getAttribute('href'));
        $this->assertSame($nonce, $link->getAttribute('nonce'));
    }

    public function testContentWithoutHeadIsUntouched()
    {
        $backend = CSPBackend::create();
        $backend->javascript('springtimesoft/silverstripe-csp-suite: tests/php/example.js');

        $content = '<p>no head tag here</p>';

        $this->assertSame($content, $backend->includeInHTML($content));
    }

    private function getFirstElement(string $html, string $tagName): \DOMElement
    {
        $document = new \DOMDocument();
        $document->loadHTML($html);

        $elements = $document->getElementsByTagName($tagName);
        $this->assertGreaterThan(0, $elements->length, "Expected a <{$tagName}> tag in the HTML output");

        return $elements->item(0);
    }
}
