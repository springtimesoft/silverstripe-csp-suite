# Troubleshooting

## Untraceable inline-script / inline-eval failures

Some browser extensions may operate in a way that violates your CSP configuration. In theory they should not cause
failures, since they aren't under the website's control, but browsers have inconsistent behaviour on this front.

This can result in CSP errors that reference code completely unrelated to your site, inducing immense confusion.
You can validate that this is the root cause by temporarily disabling all extensions in your browser and reloading.

These failures can generally be ignored if they aren't impacting your site's actual scripts or styles.

## Violations not being reported

Various conditions can result in reports failing to send:

- The CSP mode must be set to report-only or enabled with reporting.
- Chrome and other Chromium-based browsers do not send reports in non-HTTPS environments.
- Report delivery will fail in environments protected by basic auth. In some cases browsers may not try again if basic auth is removed thereafter.
- Some browsers delay the submission of reports by up to a minute in order to batch them.

See also:

- [Debug your reporting setup](https://developer.chrome.com/docs/capabilities/web-apis/reporting-api?utm_source=devtools#debug_your_reporting_setup)

## CSP failures in the CMS

The CSP definition for the CMS is fairly loose by default, but if this is still causing trouble, you can extend it
and apply your customised version over the default via configuration:

```yml
---
Name: override-cms-csp
After:
  - springtimesoft-csp-suite-csp
---

# Override CMS policy
Silverstripe\CSP\CSPMiddleware:
  policies:
    cms: App\CSP\MyCustomCMSPolicy
```
