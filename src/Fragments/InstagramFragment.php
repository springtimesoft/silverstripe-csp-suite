<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Instagram embeds within a Content Security Policy.
 */
class InstagramFragment implements Fragment
{
    public const DOMAIN = '*.instagram.com';

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::SCRIPT, self::DOMAIN)
            ->addDirective(Directive::FRAME, self::DOMAIN);
    }
}
