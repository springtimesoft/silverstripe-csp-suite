# Configuration

## Getting started

Start by setting the CSP mode in the CMS to 'Disable but report violations', so that
you can see failures.

Next, create a `Policy` that extends `Springtimesoft\CSPSuite\Policies\CorePolicy`:

```php
<?php

namespace App\CSP;

use Springtimesoft\CSPSuite\Policies\CorePolicy;

class MySitePolicy extends CorePolicy
{
    public function configure()
    {
        parent::configure();
    }
}
```

Extending `CorePolicy` and calling `parent::configure()` gives you a headstart:

- Your policy will only apply to non-CMS routes
- The CSP mode set in the CMS will be honoured
- Violations will be submitted and shown in the CSP Violations report

Next, you can enable your new policy in config:

```yml
---
Name: my-site-csp
---
Silverstripe\CSP\CSPMiddleware:
  policies:
    mysite: 'App\CSP\MySitePolicy'
```

Flush, and load your site. You will likely now see a range of CSP failures in the console,
which means the CSP is working.

## Applying rules

To address the CSP failures, you need to introduce rules permitting resources to be fetched
from trusted sources. You can add these rules directly to your Policy, but a better way is
to introduce them in the form of reusable `Fragment`s.

One example of a `Fragment` that you may need to create is to allow a tracker's scripts:

```php
<?php

use Silverstripe\CSP\Directive;
use Silverstripe\CSP\Fragments\Fragment;
use Silverstripe\CSP\Policies\Policy;

/**
 * Enables loading of scripts from Some Tracker
 */
class SomeTrackerFragment implements Fragment
{
    public static function addTo(Policy $policy): void
    {
        $policy
            ->addDirective(Directive::SCRIPT, '*.some-tracker.io');
    }
}
```

There are a range of default `Fragment`s in both the `silverstripeltd\silverstripe-csp`
module and this one that you can add directly, or use as reference when writing your own.

## Testing

As different pages in a site can contain a variety of resources, you should test all paths
with as much variation as possible in order to catch any edge-cases. For example, your
homepage may not contain video embeds, or some functionality may only be present when the
user is logged in.

To avoid negative impacts when retroactively introducing a CSP to an existing production
application, it is recommended to deploy your CSP in 'Disable but report violations' mode
initially, and monitor for violations for a period before switching to the complete
'Enable with reporting' mode.

## Additional security headers

The CSP Suite module also provides a dedicated SecurityHeaderMiddleware for applying other
security-related headers to all requests. This is disabled by default as it can impact the
behaviour of your application (in particular, it applies HSTS.)

Review the default header values it adds, and then enable it with any necessary tweaks:

```yml
Springtimesoft\CSPSuite\Middleware\SecurityHeaderMiddleware:
  enable: true
  strict_transport_security: "max-age=63072000; includeSubdomains; preload"
```
