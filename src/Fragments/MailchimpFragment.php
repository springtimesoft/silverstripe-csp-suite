<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables submission of Mailchimp newsletter forms within a Content Security Policy.
 */
class MailchimpFragment implements Fragment
{
    public const DOMAIN = '*.list-manage.com';

    public static function addTo(Policy $policy): void
    {
        $policy->addDirective(Directive::FORM_ACTION, self::DOMAIN);
    }
}
