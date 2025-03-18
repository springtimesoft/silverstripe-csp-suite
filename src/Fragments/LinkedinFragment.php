<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Facebook tracking and embeds within a Content Security Policy.
 */
class LinkedinFragment implements Fragment
{
    public const DOMAINS = [
        '*.ads.linkedin.com',
        'www.linkedin.com',
    ];

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::CONNECT, '*.ads.linkedin.com')
            ->addDirective(Directive::IMG, self::DOMAINS);
    }
}
