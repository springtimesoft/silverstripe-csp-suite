<?php

namespace Springtimesoft\CSPSuite\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class CSPViolation extends DataObject
{
    private static $table_name = 'CSPViolation';

    private static $db = [
        'ReportedTime'       => 'Datetime',
        'Disposition'        => 'Varchar(7)',
        'BlockedURI'         => 'Varchar(255)',
        'EffectiveDirective' => 'Varchar(255)',
        'Violations'         => 'Int',
    ];

    private static $many_many = [
        'Documents' => CSPDocument::class,
    ];

    private static $indexes = [
        // Index used by the ingest controller to quickly find existing violations
        'Filter' => [
            'type'    => 'index',
            'columns' => ['Disposition', 'BlockedURI', 'EffectiveDirective'],
        ],
    ];

    private static $summary_fields = [
        'ReportedTime' => 'Latest Report',
        'Disposition',
        'BlockedURI',
        'DocumentURIs',
        'EffectiveDirective',
        'Violations',
    ];

    private static $default_sort = 'ReportedTime DESC';

    /**
     * Renders a list of document URIs associated with this violation.
     */
    public function getDocumentURIs(): DBField
    {
        return DBField::create_field('Text', implode(', ', $this->Documents()->Column('URI')));
    }
}
