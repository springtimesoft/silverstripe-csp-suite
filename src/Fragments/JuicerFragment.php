<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Juicer.io within a Content Security Policy.
 */
class JuicerFragment implements Fragment
{
    public const DOMAIN = '*.juicer.io';

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::SCRIPT, self::DOMAIN)
            ->addDirective(Directive::STYLE, self::DOMAIN)
            ->addDirective(Directive::IMG, self::DOMAIN)
            ->addDirective(Directive::FONT, self::DOMAIN)
            ->addDirective(Directive::CONNECT, self::DOMAIN);
    }
}
