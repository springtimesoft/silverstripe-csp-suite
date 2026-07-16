<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\SiteConfig\SiteConfig;
use Springtimesoft\CSPSuite\Extensions\CSPSiteConfigExtension;

class CSPSiteConfigExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testProvidePermissions()
    {
        $permissions = (new CSPSiteConfigExtension())->providePermissions();

        $this->assertArrayHasKey(CSPSiteConfigExtension::ADMINISTER_CSP_PERMISSION, $permissions);
    }

    public function testFieldHiddenWithoutPermission()
    {
        $this->logInWithPermission('CMS_ACCESS_CMSMain');

        $fields = SiteConfig::current_site_config()->getCMSFields();

        $this->assertNull($fields->dataFieldByName('CSPReportingOnly'));
    }

    public function testFieldShownWithPermission()
    {
        $this->logInWithPermission(CSPSiteConfigExtension::ADMINISTER_CSP_PERMISSION);

        $fields = SiteConfig::current_site_config()->getCMSFields();
        $field  = $fields->dataFieldByName('CSPReportingOnly');

        $this->assertInstanceOf(OptionsetField::class, $field);
    }

    public function testDefaultModeIsReportingOnly()
    {
        // The Enum default is applied by the database, so write and re-read
        $id = SiteConfig::create()->write();

        $siteConfig = SiteConfig::get()->byID($id);

        $this->assertSame(CSPSiteConfigExtension::CSP_REPORTING_ONLY, $siteConfig->CSPReportingOnly);
    }
}
