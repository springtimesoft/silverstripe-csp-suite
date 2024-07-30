<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Unpkg scripts/styles within a Content Security Policy.
 */
class UnpkgFragment implements Fragment
{
    public const DOMAIN = 'unpkg.com';

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::SCRIPT, self::DOMAIN)
            ->addDirective(Directive::STYLE, self::DOMAIN);
    }
}
