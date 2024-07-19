<?php

namespace Springtimesoft\CSPSuite\Reports;

use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Reports\Report;
use Springtimesoft\CSPSuite\GridFieldComponents\ClearAllCSPViolationsAction;
use Springtimesoft\CSPSuite\Models\CSPViolation;

/**
 * Outputs CSP violations as a report in the CMS.
 * Based on original signifynz/silverstripe-security-headers implementation.
 */
class CSPViolationsReport extends Report
{
    public function title()
    {
        return _t(__CLASS__ . '.TITLE', 'CSP violations');
    }

    public function description()
    {
        $desc = _t(
            __CLASS__ . '.DESCRIPTION',
            'Lists violations caught by the Content Security Policy.'
            . ' For more details see <a href="{url}" target="_blank">the MDN documentation</a>.',
            ['url' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP#Violation_report_syntax']
        );

        return str_replace('</a>', ' <span class="font-icon-external-link"></span></a>', $desc);
    }

    public function sourceRecords($params = [], $sort = null, $limit = null)
    {
        return CSPViolation::get()->eagerLoad('Documents');
    }

    public function getReportField()
    {
        $gridField  = parent::getReportField();
        $gridConfig = $gridField->getConfig();

        $gridConfig->addComponents([
            new ClearAllCSPViolationsAction(),
            new GridFieldDeleteAction(),
        ]);

        return $gridField;
    }
}
