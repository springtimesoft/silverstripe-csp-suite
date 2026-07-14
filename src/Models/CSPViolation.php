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
        'SourceFile'         => 'Varchar(240)',
        'Violations'         => 'Int',
    ];

    private static $many_many = [
        'Documents' => CSPDocument::class,
        'UserAgents' => CSPUserAgent::class,
    ];

    private static $indexes = [
        // Index used by the ingest controller to quickly find existing violations
        'Filter' => [
            'type'    => 'index',
            'columns' => ['Disposition', 'BlockedURI', 'EffectiveDirective', 'SourceFile'],
        ],
    ];

    private static $summary_fields = [
        'ReportedTime' => 'Latest Report',
        'Disposition',
        'BlockedURI',
        'DocumentURISummary' => 'Pages',
        'EffectiveDirective',
        'SourceFile',
        'UserAgentSummary' => 'Browsers',
        'Violations',
    ];

    private static $default_sort = 'ReportedTime DESC';

    /**
     * How many document URIs will be rendered in the report summary
     */
    private static $uri_summary_limit = 5;

    /**
     * How many user agents will be rendered in the report summary
     */
    private static $user_agent_summary_limit = 5;

    /**
     * Renders a full list of document URIs associated with this violation.
     */
    public function getDocumentURIList(): DBField
    {
        return DBField::create_field('Text', implode(', ', $this->Documents()->Column('URI')));
    }

    /**
     * Renders a summary of document URIs associated with this violation.
     */
    public function getDocumentURISummary(): DBField
    {
        $limit = self::config()->get('uri_summary_limit');

        $count        = $this->Documents()->count();
        $documentURIs = $this->Documents()->limit($limit)->Column('URI');
        if ($count > $limit) {
            $more = _t(__CLASS__ . '.MORE', 'and {count} more', ['count' => $count - $limit]);
            return DBField::create_field('Text', implode(', ', [...$documentURIs, $more]));
        }

        return DBField::create_field('Text', implode(', ', $documentURIs));
    }

    /**
     * Renders a full list of user agents (browsers) associated with this violation.
     */
    public function getUserAgentList(): DBField
    {
        $userAgents = $this->UserAgents()->limit($limit)->Map('Name', 'Raw')->toArray();

        $formatted = array_map(
            fn (string $name, string $raw) => "{$name} ({$raw})",
            array_keys($userAgents),
            array_values($userAgents)
        );

        return DBField::create_field('Text', implode(', ', $this->UserAgents()->Column('Name')));
    }

    /**
     * Renders a summary of user agents (browsers) associated with this violation.
     */
    public function getUserAgentSummary(): DBField
    {
        $limit = self::config()->get('user_agent_summary_limit');

        $count      = $this->UserAgents()->count();
        $userAgents = $this->UserAgents()->limit($limit)->Map('Name', 'Raw')->toArray();

        $formattedUserAgents = array_map(
            fn (string $name, string $raw) => "<span title=\"{$raw}\">{$name}</span>",
            array_keys($userAgents),
            array_values($userAgents)
        );

        if ($count > $limit) {
            $more = _t(__CLASS__ . '.MORE', 'and {count} more', ['count' => $count - $limit]);
            return DBField::create_field('Text', implode(', ', [...$formattedUserAgents, $more]));
        }

        return DBField::create_field('HTMLVarchar', implode(', ', $formattedUserAgents));
    }
}
