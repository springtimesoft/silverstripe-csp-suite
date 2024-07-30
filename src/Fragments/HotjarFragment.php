<?php

namespace Springtimesoft\CSPSuite\Fragments;

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Keyword;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables usage of Hotjar within a Content Security Policy. This requires inclusion of
 * unsafe-inline permissions for scripts and styles, and should only be applied if
 * absolutely necessary.
 *
 * @see https://help.hotjar.com/hc/en-us/articles/115011640307-Content-Security-Policies
 */
class HotjarFragment implements Fragment
{
    public const DOMAINS = [
        '*.hotjar.com',
        '*.hotjar.io',
    ];

    public const WEBSOCKET = 'wss://*.hotjar.com';

    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::SCRIPT, self::DOMAINS)
            ->addDirective(Directive::IMG, self::DOMAINS)
            ->addDirective(Directive::FRAME, self::DOMAINS)
            ->addDirective(Directive::CONNECT, self::DOMAINS)
            ->addDirective(Directive::CONNECT, self::WEBSOCKET);

        $policy
            ->addDirective(Directive::SCRIPT, Keyword::UNSAFE_INLINE)
            ->addDirective(Directive::STYLE, Keyword::UNSAFE_INLINE);
    }
}
