<?php

namespace Springtimesoft\CSPSuite\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Exposes settings for configuring the CSP mode within the CMS. Field is based on
 * the signifynz/silverstripe-security-headers module for backwards compatibility.
 */
class CSPSiteConfigExtension extends DataExtension implements PermissionProvider
{
    public const ADMINISTER_CSP_PERMISSION = 'ADMINISTER_CSP';

    public const CSP_WITH_REPORTING = '0';

    public const CSP_REPORTING_ONLY = '1';

    public const CSP_WITHOUT_REPORTING = '2';

    public const CSP_DISABLE = '3';

    private static $db = [
        'CSPReportingOnly' => "Enum('0,2,1,3', '1')",
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if (!Permission::check(self::ADMINISTER_CSP_PERMISSION)) {
            $fields->removeByName('CSPReportingOnly');

            return;
        }

        $fields->addFieldToTab(
            'Root.Main',
            OptionsetField::create(
                'CSPReportingOnly',
                'Content Security Policy',
                [
                    self::CSP_WITH_REPORTING    => 'Enable with reporting (recommended)',
                    self::CSP_WITHOUT_REPORTING => 'Enable without reporting',
                    self::CSP_REPORTING_ONLY    => 'Disable but report violations',
                    self::CSP_DISABLE           => 'Disable (not recommended)',
                ]
            )
        );
    }

    public function providePermissions()
    {
        return [
            self::ADMINISTER_CSP_PERMISSION => [
                'name'     => 'Administer CSP',
                'category' => 'Content Security Policy',
                'help'     => 'Can administer settings related to the Content Security Policy',
            ],
        ];
    }
}
