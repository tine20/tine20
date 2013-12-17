<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once 'vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Tinebase_WebDav_Plugin_Inverse
 */
class Tinebase_WebDav_Plugin_InverseTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * 
     * @var Sabre\DAV\Server
     */
    protected $server;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar CalDAV PluginInverse Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        
        $this->plugin = new Tinebase_WebDav_Plugin_Inverse();
        
        $this->server->addPlugin($this->plugin);
        
        $this->response = new Sabre\HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();
        
        $this->assertEquals('Tinebase_WebDav_Plugin_Inverse', $pluginName);
    }
    
    /**
     * test getSupportedReportSet method
     */
    public function testGetSupportedReportSet()
    {
        $set = $this->plugin->getSupportedReportSet('/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id);
        
        $this->assertContains('{' . Tinebase_WebDav_Plugin_Inverse::NS_DAV . '}principal-match', $set);
        $this->assertContains('{' . Tinebase_WebDav_Plugin_Inverse::NS_INVERSE . '}acl-query',   $set);
        $this->assertContains('{' . Tinebase_WebDav_Plugin_Inverse::NS_INVERSE . '}user-query',  $set);
    }
    
    /**
     * test aclQuery->userList method
     */
    public function testAclQueryUserList()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <acl-query xmlns="urn:inverse:params:xml:ns:inverse-dav">
                     <user-list/>
                 </acl-query>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<user><id>' . Tinebase_Core::getUser()->contact_id . '</id>', $this->response->body);
    }
    
    /**
     * test aclQuery method
     */
    public function testAclQueryRoles()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <acl-query xmlns="urn:inverse:params:xml:ns:inverse-dav">
                     <roles user="' . Tinebase_Core::getUser()->contact_id . '"/>
                 </acl-query>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<roles><ObjectViewer/><ObjectCreator/><ObjectEditor/><ObjectEraser/><PrivateViewer/></roles>', $this->response->body);
    }
    
    /**
     * test aclQuery method
     */
    public function testAclAddUser()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <acl-query xmlns="urn:inverse:params:xml:ns:inverse-dav">
                     <add-user user="' . array_value('pwulf', Zend_Registry::get('personas'))->contact_id . '"/>
                 </acl-query>';
        
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->objects['initialContainer']->id);
        $this->assertContains(array_value('pwulf', Zend_Registry::get('personas'))->accountId, $grants->account_id);
    }
    
    /**
     * test aclQuery method
     */
    public function testAclRemoveUser()
    {
        // add user to grants of container
        $this->testAclAddUser();
        
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <acl-query xmlns="urn:inverse:params:xml:ns:inverse-dav">
                     <remove-user user="' . array_value('pwulf', Zend_Registry::get('personas'))->contact_id . '"/>
                 </acl-query>';
        
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->objects['initialContainer']->id);
        $this->assertNotContains(array_value('pwulf', Zend_Registry::get('personas'))->accountId, $grants->account_id);
    }
    
    /**
     * test aclQuery method
     */
    public function testAclSetRoles()
    {
        // add user to grants of container
        $this->testAclAddUser();
        
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <acl-query xmlns="urn:inverse:params:xml:ns:inverse-dav">
                     <set-roles user="' . array_value('pwulf', Zend_Registry::get('personas'))->contact_id . '">
                         <ObjectEraser/>
                     </set-roles>
                 </acl-query>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        
        $grantsOfPwulf = Tinebase_Container::getInstance()
            ->getGrantsOfContainer($this->objects['initialContainer']->id)
            ->filter('account_id', array_value('pwulf', Zend_Registry::get('personas'))->accountId)
            ->getFirstRecord();
        $this->assertTrue($grantsOfPwulf->deleteGrant);
        $this->assertFalse($grantsOfPwulf->addGrant);
    }
    
    /**
     * test principalMatch method
     */
    public function testPrincipalMatch()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <D:principal-match xmlns:D="DAV:">
                     <D:principal-property>
                         <D:owner/>
                     </D:principal-property>
                 </D:principal-match>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<d:response><d:href>/principals/users/' . Tinebase_Core::getUser()->contact_id . '</d:href>', $this->response->body);
    }
    
    /**
     * test userQuery method
     */
    public function testUserQuery()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
                 <user-query xmlns="urn:inverse:params:xml:ns:inverse-dav">
                     <users match-name="' . Tinebase_Core::getUser()->accountFullName . '"/>
                 </user-query>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<displayName>' . Tinebase_Core::getUser()->accountDisplayName . '</displayName>', $this->response->body);
        $this->assertContains('<id>' . Tinebase_Core::getUser()->contact_id . '</id>', $this->response->body);
    }
}
