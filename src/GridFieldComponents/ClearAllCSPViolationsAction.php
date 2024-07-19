<?php

namespace Springtimesoft\CSPSuite\GridFieldComponents;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\AbstractGridFieldComponent;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Springtimesoft\CSPSuite\Models\CSPViolation;

/**
 * A GridField component that clears all CSP violation data.
 */
class ClearAllCSPViolationsAction extends AbstractGridFieldComponent implements GridField_ActionProvider, GridField_HTMLProvider, GridField_URLHandler
{
    private $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = 'buttons-before-left')
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the print button in a <p> tag below the field
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField,
            'clear',
            _t(__CLASS__ . '.title', 'Clear all'),
            'clear',
            null
        );
        $button->setForm($gridField->getForm());

        $button->addExtraClass('font-icon-delete grid-delete-button btn btn-outline-danger');

        return [
            $this->targetFragment => $button->Field(),
        ];
    }

    public function getTitle($gridField, $record, $columnName)
    {
        return 'Clear all CSP violations';
    }

    /**
     * Which GridField actions are this component handling.
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return ['clear'];
    }

    /**
     * Map URL handlers to action methods.
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'clear' => 'handleClear',
        ];
    }

    /**
     * Handle the actions and apply any changes to the GridField.
     *
     * @param string $actionName
     * @param mixed  $arguments
     * @param array  $data       - form data
     *
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ('clear' === $actionName) {
            $this->handleClear();
        }
    }

    public function handleClear()
    {
        // Drop all CSPViolation and CSPDocument records, including relations
        $cspViolationTable = DataObject::getSchema()->baseDataTable(CSPViolation::class);
        SQLDelete::create("\"{$cspViolationTable}\"")->execute();

        $cspViolationDocumentJoin      = DataObject::getSchema()->manyManyComponent(CSPViolation::class, 'Documents');
        $cspViolationDocumentJoinTable = $cspViolationDocumentJoin['join'];
        SQLDelete::create("\"{$cspViolationDocumentJoinTable}\"")->execute();

        $cspDocumentTable = DataObject::getSchema()->baseDataTable(CSPDocument::class);
        SQLDelete::create("\"{$cspDocumentTable}\"")->execute();

        Controller::curr()->getResponse()
            ->setStatusCode(200)
            ->addHeader('X-Status', 'All CSP violations cleared.');
    }
}
