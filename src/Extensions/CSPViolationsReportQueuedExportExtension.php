<?php

namespace Springtimesoft\CSPSuite\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\GridfieldQueuedExport\Forms\GridFieldQueuedExportButton;

/**
 * When the Queued Export module is available, this extension swaps out the CSV export implementation in the report.
 */
class CSPViolationsReportQueuedExportExtension extends Extension
{
    protected function updateCMSFields(FieldList $fields)
    {
        $gridField = $fields->fieldByName('Report');
        if (empty($gridField)) {
            return;
        }

        $config = $gridField->getConfig();
        $oldExportButton = $config->getComponentByType(GridFieldExportButton::class);
        $config->addComponent($newExportButton = new GridFieldQueuedExportButton('buttons-before-left'));

        // Set Header and Export columns on new Export Button
        $newExportButton->setCsvHasHeader($oldExportButton->getCsvHasHeader());
        $newExportButton->setExportColumns($oldExportButton->getExportColumns());

        $config->removeComponentsByType(GridFieldExportButton::class);
    }
}
