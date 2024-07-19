# Silverstripe CSP Suite

This module bundles a set of tools to help you implement and manage a Content Security Policy (CSP) and related
security elements for your Silverstripe project.

## Architecture and acknowledgements

This module sits atop `silverstripeltd/silverstripe-csp`, which provides a clean and highly adaptable API for defining
CSP rules, and adds subresource integrity (SRI) and nonce support to the Requirements API to make compliance simple.
We then build upon this with CMS-based mode settings, violation report collection, and a report UI within the CMS,
much of which is adapted from the `signify-nz/silverstripe-security-headers` module.

Each of those projects has unique strengths and functionality, and this module aims to provide a comprehensive CSP
solution combining the best elements of both.

- [`silverstripeltd/silverstripe-csp`](https://github.com/silverstripeltd/silverstripe-csp)
- [`signify-nz/silverstripe-security-headers`](https://github.com/signify-nz/silverstripe-security-headers)

## Requirements

- Silverstripe CMS ^5.2 ([eager-loading](https://docs.silverstripe.org/en/5/developer_guides/model/relations/#eager-loading) support required)

## Installation

```sh
composer require springtimesoft/silverstripe-csp-suite
composer require symbiote/silverstripe-queuedjobs # Optional but strongly recommended
```

This module relies on Queued Jobs to perform regular cleanup tasks. Without a job runner configured, excessive records
will build up over time if CSP violations are regularly triggered. The cleanup jobs are configured to run automatically
once a day when Queued Jobs is operational.

## License

See [License](LICENSE.md).

## Documentation

- [Configuration](docs/en/Configuration.md)
- [Troubleshooting](docs/en/Troubleshooting.md)
