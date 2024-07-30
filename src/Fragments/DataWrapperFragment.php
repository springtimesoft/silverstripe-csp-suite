<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of DataWrapper embeds within a Content Security Policy.
 */
class DataWrapperFragment implements Fragment
{
    public const DOMAIN = 'datawrapper.dwcdn.net';

    public static function addTo(Policy $policy): void
    {
        $policy->addDirective(Directive::FRAME, self::DOMAIN);
    }
}
