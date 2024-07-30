<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Google Fonts within a Content Security Policy.
 */
class GoogleFontsFragment implements Fragment
{
    public const DOMAIN = 'fonts.gstatic.com';

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::FONT, self::DOMAIN)
            ->addDirective(Directive::IMG, self::DOMAIN);
    }
}
