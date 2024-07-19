<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Keyword;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of inline styles within a Content Security Policy.
 */
class InlineStylesFragment implements Fragment
{
    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::STYLE_ATTR, Keyword::UNSAFE_INLINE);
    }
}
