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
    
    public function setUp()
    {
        //$this->setBrowser('*firefox');
        //$this->setBrowserUrl('http://localhost/tt/tine20/');
    }
    
    public function testLogin()
    {
        $this->waitForElementPresent('username');
        
        $this->type('username', 'tine20admin');
        $this->type('password', 'super');
        
        $loginButtonId = $this->getEval("window.Tine.loginPanel.getLoginPanel().getForm().getEl().query('button')[0].id");
        $this->click($loginButtonId);
        
        $extMsgOkButtonId = $this->getEval("window.Ext.MessageBox.getDialog().buttons[0].id");
        $extMsgYesButtonId = $this->getEval("window.Ext.MessageBox.getDialog().buttons[1].id");
        $extMsgNoButtonId = $this->getEval("window.Ext.MessageBox.getDialog().buttons[2].id");
        $extMsgCancelButtonId = $this->getEval("window.Ext.MessageBox.getDialog().buttons[3].id");
        
        $this->waitForVisible($extMsgOkButtonId);
        $this->click($extMsgOkButtonId);
        
        $this->type('password', 'lars');
        $this->click($loginButtonId);
    }
    
    public function testLogout()
    {
        sleep(3);
    }
}
