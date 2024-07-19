<?php

namespace Springtimesoft\CSPSuite\Models;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;

class CSPDocument extends DataObject
{
    private static $table_name = 'CSPDocument';

    private static $db = [
        'URI' => 'Varchar(255)',
    ];

    private static $has_one = [
        'SiteTree' => SiteTree::class,
    ];

    private static $belongs_many_many = [
        'CSPViolations' => CSPViolation::class,
    ];
}
