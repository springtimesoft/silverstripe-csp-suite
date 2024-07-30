<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Facebook tracking and embeds within a Content Security Policy.
 */
class FacebookFragment implements Fragment
{
    public const DOMAINS = [
        '*.facebook.net',
        '*.facebook.com',
    ];

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::CONNECT, self::DOMAINS)
            ->addDirective(Directive::FORM_ACTION, self::DOMAINS)
            ->addDirective(Directive::FRAME, self::DOMAINS)
            ->addDirective(Directive::IMG, self::DOMAINS)
            ->addDirective(Directive::SCRIPT, self::DOMAINS)
            ->addDirective(Directive::STYLE, self::DOMAINS);
    }
}
