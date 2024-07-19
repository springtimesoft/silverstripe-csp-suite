<?php

namespace Springtimesoft\CspSuite\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Config;

/**
 * Applies common security headers to all HTTP responses for the application.
 *
 * Disabled by default - review and adjust the values to suit your application before enabling.
 */
class SecurityHeaderMiddleware implements HTTPMiddleware
{
    private static $enable = false;

    /**
     * Manages HSTS support, which automatically upgrades requests to HTTPS at the browser level.
     * By default this is set to 2 years. You may also want to add the `preload` directive if you
     * want to submit the site to the HSTS preload list.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
     */
    private static $strict_transport_security = 'max-age=63072000';

    /**
     * Prevents the browser from MIME-sniffing and interpreting a response in an alternate format
     * to the declared content-type.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
     */
    private static $content_type_options = 'nosniff';

    /**
     * Disables framing of the application by other domains. Changing this to DENY is not
     * recommended, as it will prevent CMS preview panes from working.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
     */
    private static $frame_options = 'SAMEORIGIN';

    /**
     * Controls the behaviour of the Referer header when navigating between sites. The default
     * mode will only expose this header in requests to the site itself.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy
     */
    private static $referrer_policy = 'same-origin';

    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);

        if (!Config::inst()->get(self::class, 'enable')) {
            return $response;
        }

        if ($sts = Config::inst()->get(self::class, 'strict_transport_security')) {
            $response->addHeader('Strict-Transport-Security', $sts);
        }

        if ($contentTypeOptions = Config::inst()->get(self::class, 'content_type_options')) {
            $response->addHeader('X-Content-Type-Options', $contentTypeOptions);
        }

        if ($frameOptions = Config::inst()->get(self::class, 'frame_options')) {
            $response->addHeader('X-Frame-Options', $frameOptions);
        }

        if ($referrerPolicy = Config::inst()->get(self::class, 'referrer_policy')) {
            $response->addHeader('Referrer-Policy', $referrerPolicy);
        }

        return $response;
    }
}
