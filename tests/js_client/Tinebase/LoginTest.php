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
        
        $logoutButtonId = $this->getEval("window.Ext.getCmp('tineMenu').items.last().getEl().id");
        $this->click($logoutButtonId);
        
        Ext_MessageBox::getInstance($this)->pressYes();
        $this->waitForPageToLoad();
    }
    
    public function testLogin()
    {
        
        $this->waitForElementPresent('username');
        
        $this->type('username', Zend_Registry::get('testConfig')->username);
        $this->type('password', rand(10000, 99999999));
        
        $loginButtonId = $this->getEval("window.Tine.loginPanel.getLoginPanel().getForm().getEl().query('button')[0].id");
        $this->click($loginButtonId);
        
        Ext_MessageBox::getInstance($this)->pressOK();
        
        $this->type('password', Zend_Registry::get('testConfig')->password);
        $this->click($loginButtonId);
        
        $this->waitForElementPresent('tineMenu');
    }

}
