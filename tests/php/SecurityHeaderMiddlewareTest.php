<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use Springtimesoft\CSPSuite\Middleware\SecurityHeaderMiddleware;

class SecurityHeaderMiddlewareTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testHeadersNotAddedWhenDisabled()
    {
        // The host project may enable the middleware via YAML, so set it explicitly
        Config::modify()->set(SecurityHeaderMiddleware::class, 'enable', false);

        $response = $this->runMiddleware();

        $this->assertNull($response->getHeader('Strict-Transport-Security'));
        $this->assertNull($response->getHeader('X-Content-Type-Options'));
        $this->assertNull($response->getHeader('X-Frame-Options'));
        $this->assertNull($response->getHeader('Referrer-Policy'));
    }

    public function testHeadersAddedWhenEnabled()
    {
        Config::modify()->set(SecurityHeaderMiddleware::class, 'enable', true);

        $response = $this->runMiddleware();

        $this->assertSame('max-age=63072000', $response->getHeader('Strict-Transport-Security'));
        $this->assertSame('nosniff', $response->getHeader('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->getHeader('X-Frame-Options'));
        $this->assertSame('same-origin', $response->getHeader('Referrer-Policy'));
    }

    public function testIndividualHeaderCanBeDisabled()
    {
        Config::modify()->set(SecurityHeaderMiddleware::class, 'enable', true);
        Config::modify()->set(SecurityHeaderMiddleware::class, 'frame_options', '');

        $response = $this->runMiddleware();

        $this->assertNull($response->getHeader('X-Frame-Options'));
        $this->assertSame('max-age=63072000', $response->getHeader('Strict-Transport-Security'));
        $this->assertSame('nosniff', $response->getHeader('X-Content-Type-Options'));
        $this->assertSame('same-origin', $response->getHeader('Referrer-Policy'));
    }

    private function runMiddleware(): HTTPResponse
    {
        $middleware = new SecurityHeaderMiddleware();

        return $middleware->process(
            new HTTPRequest('GET', '/'),
            fn () => new HTTPResponse()
        );
    }
}
