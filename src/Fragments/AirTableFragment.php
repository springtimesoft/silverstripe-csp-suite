<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of AirTable graphics within a Content Security Policy.
 */
class AirTableFragment implements Fragment
{
    public const DOMAIN = '*.airtableusercontent.com';

    public static function addTo(Policy $policy): void
    {
        $policy->addDirective(Directive::IMG, self::DOMAIN);
    }
}
