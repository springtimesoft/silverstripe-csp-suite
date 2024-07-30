<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Cloudflare Insights within a Content Security Policy.
 */
class CloudflareInsightsFragment implements Fragment
{
    public const DOMAIN = 'static.cloudflareinsights.com';

    public static function addTo(Policy $policy): void
    {
        $policy->addDirective(Directive::SCRIPT, self::DOMAIN);
    }
}
