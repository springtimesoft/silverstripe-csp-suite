<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Keyword;
use Silverstripe\CSP\Policies\Policy;

/**
 * Applies strict-dynamic rule to the script-src directive of a Content Security Policy.
 *
 * The strict-dynamic rule disables most other rules, such as unsafe-inline and domain definitions, and instead expects
 * all scripts on the page to be covered by a nonce. In exchange, it trusts valid scripts to inject their own scripts,
 * allowing for tools like Google Tag Manager to operate unhindered. Note that this trust does not extend to injected
 * styles, which must still be permitted via an unsafe-inline rule against style-src.
 *
 * Scripts that cannot have a nonce applied (e.g. the default Cloudflare Analytics JS injection method) can't be used in
 * combination with strict-dynamic. This fragment should be applied carefully and tested thoroughly.
 */
class StrictDynamicFragment implements Fragment
{
    public static function addTo(Policy $policy): void
    {
        $policy->addDirective(Directive::SCRIPT, Keyword::STRICT_DYNAMIC);
    }
}
