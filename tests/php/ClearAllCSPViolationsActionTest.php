<?php

namespace Springtimesoft\CSPSuite\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLSelect;
use Springtimesoft\CSPSuite\GridFieldComponents\ClearAllCSPViolationsAction;
use Springtimesoft\CSPSuite\Models\CSPDocument;
use Springtimesoft\CSPSuite\Models\CSPUserAgent;
use Springtimesoft\CSPSuite\Models\CSPViolation;

class ClearAllCSPViolationsActionTest extends SapphireTest
{
    protected static $fixture_file = 'CSPViolationTest.yml';

    public function testHandleClearDeletesAllViolationData()
    {
        $this->assertCount(1, CSPViolation::get());
        $this->assertCount(3, CSPDocument::get());
        $this->assertCount(3, CSPUserAgent::get());

        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));

        $controller = new Controller();
        $controller->setRequest($request);
        $controller->setResponse(new HTTPResponse());
        $controller->pushCurrent();

        try {
            (new ClearAllCSPViolationsAction())->handleClear();

            $this->assertCount(0, CSPViolation::get());
            $this->assertCount(0, CSPDocument::get());

            foreach (['Documents', 'UserAgents'] as $relation) {
                $joinTable = DataObject::getSchema()->manyManyComponent(CSPViolation::class, $relation)['join'];
                $joinCount = SQLSelect::create('COUNT(*)', "\"{$joinTable}\"")->execute()->value();
                $this->assertSame(0, (int) $joinCount, "{$relation} join table should be emptied");
            }

            $this->assertCount(0, CSPUserAgent::get());

            $this->assertSame(
                'All CSP violations cleared.',
                $controller->getResponse()->getHeader('X-Status')
            );
        } finally {
            $controller->popCurrent();
        }
    }

    public function testActionAndURLHandlerRegistration()
    {
        $action    = new ClearAllCSPViolationsAction();
        $gridField = new GridField('TestGrid');

        $this->assertSame(['clear'], $action->getActions($gridField));
        $this->assertSame(['clear' => 'handleClear'], $action->getURLHandlers($gridField));
    }
}
