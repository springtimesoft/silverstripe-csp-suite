<?php

namespace Springtimesoft\CSPSuite\Models;

use SilverStripe\ORM\DataObject;

/**
 * Represents a unique user agent string sent by a browser.
 */
class CSPUserAgent extends DataObject
{
    private static $table_name = 'CSPUserAgent';

    private static $db = [
        'Name' => 'Varchar(250)',
        'Raw'  => 'Varchar(500)',
    ];

    private static $indexes = [
        'Raw' => ['type' => 'unique'],
    ];

    private static $belongs_many_many = [
        'CSPViolations' => CSPViolation::class,
    ];
}
