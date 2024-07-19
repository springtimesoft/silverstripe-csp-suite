<?php

namespace Springtimesoft\CSPSuite\Policies;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Silverstripe\CSP\Policies\Basic;
use SilverStripe\SiteConfig\SiteConfig;
use Springtimesoft\CSPSuite\Extensions\CSPSiteConfigExtension;

class CorePolicy extends Basic
{
    public function configure()
    {
        $cspMode = SiteConfig::current_site_config()->CSPReportingOnly;

        if (CSPSiteConfigExtension::CSP_DISABLE === $cspMode) {
            return;
        }

        parent::configure();

        // Don't proceed with reporting configuration if disabled
        if (CSPSiteConfigExtension::CSP_WITHOUT_REPORTING === $cspMode) {
            return;
        }

        // Set report-only flag if configured
        if (CSPSiteConfigExtension::CSP_REPORTING_ONLY === $cspMode) {
            $this->reportOnly();
        }

        // Set reporting URL to internal endpoint
        $this->reportTo(Director::absoluteURL('/csp-violations'));
    }

    public function shouldBeApplied(HTTPRequest $request, HTTPResponse $response): bool
    {
        $url = $request->getURL();

        // CSP for CMS routes is handled by CSP module
        if (0 === strpos($url, 'admin') || 0 === strpos($url, 'Security')) {
            return false;
        }

        $cspMode = SiteConfig::current_site_config()->CSPReportingOnly;

        return CSPSiteConfigExtension::CSP_DISABLE !== $cspMode;
    }
}
