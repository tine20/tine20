<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_LoginTest extends SessionTestCase
{
    
    public function testLogut()
    {
        $this->waitForElementPresent('tineMenu');
        
        Zend_Registry::get('log')->info('Press logout button');
        
        $logoutButtonId = $this->getEval("window.Ext.getCmp('tineMenu').items.last().getEl().id");
        $this->click($logoutButtonId);
        
        Zend_Registry::get('log')->info('Confirm logout');
        Ext_MessageBox::getInstance($this)->pressYes();
        $this->waitForPageToLoad();
    }
    
    public function testLogin()
    {
        $loginPanel = new Tinebase_DOMProxy_LoginPanel(NULL, NULL, $this);
        $loginPanel->findField('username')->waitForVisible();
        
        Zend_Registry::get('log')->info('Try login with wrong password');
        $loginPanel->setField("username", Zend_Registry::get('testConfig')->username);
        $loginPanel->setField("password", rand(10000, 99999999));
        
        $loginPanel->pressLogin();
        
        Ext_MessageBox::getInstance($this)->pressOK();
        Zend_Registry::get('log')->info('Confirm login failure');
        
        Zend_Registry::get('log')->info('Use Correct Password');
        $loginPanel->setField("password", Zend_Registry::get('testConfig')->password);
        $loginPanel->pressLogin();
        
        $this->waitForElementPresent('tineMenu');
    }

}
