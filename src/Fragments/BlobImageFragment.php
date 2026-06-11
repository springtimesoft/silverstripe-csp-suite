<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;
use Silverstripe\CSP\Scheme;

/**
 * Enables usage of blob URI images within a Content Security Policy.
 */
class BlobImageFragment implements Fragment
{
    public static function addTo(Policy $policy): void
    {
        $policy->addDirective(Directive::IMG, Scheme::BLOB);
    }
}
