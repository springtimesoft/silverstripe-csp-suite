<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Typekit fonts within a Content Security Policy.
 */
class TypekitFragment implements Fragment
{
    public const DOMAIN = '*.typekit.net';

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::CONNECT, self::DOMAIN)
            ->addDirective(Directive::FONT, self::DOMAIN)
            ->addDirective(Directive::IMG, self::DOMAIN)
            ->addDirective(Directive::SCRIPT, self::DOMAIN)
            ->addDirective(Directive::STYLE, self::DOMAIN);
    }
}
