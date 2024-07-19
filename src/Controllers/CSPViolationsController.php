<?php

namespace Springtimesoft\CSPSuite\Controllers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Springtimesoft\CSPSuite\Models\CSPViolation;

/**
 * Ingests Content Security Policy violation reports. Supports both the report-uri and report-to directives.
 * Based on original signifynz/silverstripe-security-headers implementation.
 */
class CSPViolationsController extends Controller
{
    public const REPORT_TIME = 'ReportedTime';

    public const DISPOSITION = 'Disposition';

    public const BLOCKED_URI = 'BlockedURI';

    public const EFFECTIVE_DIRECTIVE = 'EffectiveDirective';

    public const DOCUMENT_URI = 'URI';

    public const REPORT_DIRECTIVE = 'ReportDirective';

    private static $url_handlers = [
        'POST /' => 'index',
    ];

    private static $allowed_actions = [
        'index',
    ];

    /**
     * Ingest a Content Security Policy violation report.
     */
    public function index(HTTPRequest $request)
    {
        if (!$this->isSameOrigin($request) || !$this->isReport($request)) {
            return $this->httpError(400);
        }

        // Depending on which directive was used to generate the report, the format will be slightly different.
        // We must do some pre-processing on the report to normalise the data.
        $json = json_decode($request->getBody(), true);

        if (isset($json['csp-report'])) {
            // This report was sent as a result of the "report-uri" directive.
            $report = $json['csp-report'];

            $report[self::REPORT_TIME]      = DBDatetime::now()->getValue();
            $report[self::REPORT_DIRECTIVE] = 'report-uri';

            $this->processReport($report);
        } else {
            // This report was sent as a result of the "report-to" directive.
            // There may be multiple reports in the one request.
            foreach ($json as $reportWrapper) {
                if ('csp-violation' == $reportWrapper['type']) {
                    $report = $reportWrapper['body'];

                    // 'age' is the number of milliseconds since the report was generated.
                    $age = time() - ($reportWrapper['age'] / 1000);

                    $report[self::REPORT_TIME]      = DBField::create_field('Datetime', $age)->getValue();
                    $report[self::REPORT_DIRECTIVE] = 'report-to';

                    $this->processReport($report);
                }
            }
        }
    }

    /**
     * Process a Content Security Policy violation report.
     * Creates or updates the relevant CSPViolation object.
     */
    public function processReport(array $cspReport)
    {
        $violation = $this->getOrCreateViolation($cspReport);

        $this->setDocument($cspReport, $violation);
        ++$violation->Violations;
        $reportTime = $this->getDataForAttribute($cspReport, self::REPORT_TIME);

        if (null === $violation->{self::REPORT_TIME} || $violation->{self::REPORT_TIME} < $reportTime) {
            $violation->{self::REPORT_TIME} = $reportTime;
        }

        $violation->write();
    }

    /**
     * If this violation has been previously reported, get that violation object. Otherwise, create a new one.
     */
    protected function getOrCreateViolation(array $cspReport): CSPViolation
    {
        $violationData = [
            self::DISPOSITION         => $this->getDataForAttribute($cspReport, self::DISPOSITION),
            self::BLOCKED_URI         => $this->getDataForAttribute($cspReport, self::BLOCKED_URI),
            self::EFFECTIVE_DIRECTIVE => $this->getDataForAttribute($cspReport, self::EFFECTIVE_DIRECTIVE),
        ];

        $violation = CSPViolation::get()->filter($violationData)->first();
        if (!$violation) {
            $violationData['Violations'] = 0;

            $violation = CSPViolation::create($violationData);
        }

        return $violation;
    }

    /**
     * Set the document-uri for a given violation based on the report.
     */
    protected function setDocument(array $cspReport, CSPViolation $violation)
    {
        $documentURI = $this->getDataForAttribute($cspReport, self::DOCUMENT_URI);
        // If the document is already added to this violation, no need to re-add it.
        if ($violation->Documents()->find('URI', $documentURI)) {
            return;
        }

        $documentData = [
            self::DOCUMENT_URI => $documentURI,
        ];
        $document = CSPDocument::get()->filter($documentData)->first();

        if (!$document) {
            $document     = CSPDocument::create($documentData);
            $siteTreeLink = $documentURI;

            if ($siteTree = SiteTree::get_by_link($siteTreeLink)) {
                $document->SiteTreeID = $siteTree->ID;
            }
            $document->write();
        }

        $violation->Documents()->add($document);
    }

    /**
     * Get the data from the report for a given attribute.
     * The reports generated by the report-to and report-uri directives have different keys.
     */
    protected function getDataForAttribute(array $cspReport, string $attribute): mixed
    {
        if (!in_array($cspReport[self::REPORT_DIRECTIVE], ['report-uri', 'report-to'])) {
            return null;
        }

        if ('report-uri' === $cspReport[self::REPORT_DIRECTIVE]) {
            switch ($attribute) {
                case self::REPORT_TIME:
                    return $this->normaliseDateTime($cspReport[self::REPORT_TIME]);

                case self::DISPOSITION:
                    if (!empty($cspReport['disposition'])) {
                        return $cspReport['disposition'];
                    }

                    // Firefox doesn't report the disposition.
                    return 'unknown';

                case self::BLOCKED_URI:
                    return $cspReport['blocked-uri'];

                case self::EFFECTIVE_DIRECTIVE:
                    if (!empty($cspReport['effective-directive'])) {
                        return $cspReport['effective-directive'];
                    }

                    // Firefox doesn't report the effective directive.
                    return $cspReport['violated-directive'];

                case self::DOCUMENT_URI:
                    return $cspReport['document-uri'];

                default:
                    return null;
            }
        }

        switch ($attribute) {
            case self::REPORT_TIME:
                return $this->normaliseDateTime($cspReport[self::REPORT_TIME]);

            case self::DISPOSITION:
                return $cspReport['disposition'];

            case self::BLOCKED_URI:
                return $cspReport['blockedURL'];

            case self::EFFECTIVE_DIRECTIVE:
                return $cspReport['effectiveDirective'];

            case self::DOCUMENT_URI:
                return $cspReport['documentURL'];

            default:
                return null;
        }
    }

    /**
     * Removes the seconds from a datetime string for easier comparisons.
     *
     * The datetime-local Chrome implementation doesn't include seconds, so it's easiest to just not include
     * seconds at all so that exact matches work as expected.
     *
     * @var string
     *
     * @param mixed $datetime
     */
    protected function normaliseDateTime(string $datetime): string
    {
        return preg_replace('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}):\d{2}/', '$1', $datetime);
    }

    /**
     * If the origin header is set, return true if it is the same as the current absolute base URL.
     */
    protected function isSameOrigin(HTTPRequest $request): bool
    {
        $origin = $request->getHeader('origin');

        // The origin header may not be set for report-to requests, so null must be considered sameorigin.
        if (null === $origin) {
            return true;
        }

        // If not using report-to, or the origin header is set, only allow same origin requests.
        return $origin == Director::protocolAndHost();
    }

    /**
     * Returns true if the content-type of the request is a valid CSP report value.
     */
    protected function isReport(HTTPRequest $request): bool
    {
        return in_array($request->getHeader('content-type'), [
            'application/csp-report', // from report-uri directive
            'application/reports+json', // from report-to directive
            'application/json', // fallback
        ]);
    }
}
