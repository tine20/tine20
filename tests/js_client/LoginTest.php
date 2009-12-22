<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class LoginTest extends PHPUnit_Extensions_SeleniumTestCase
 {
    
    public function setUp()
    {
        $this->setBrowser('*firefox');
        $this->setBrowserUrl('http://localhost/tt/tine20/');
    }
    
    public function testLogin()
    {
        $this->open('http://localhost/tt/tine20/');
        
        // maximize window
        $this->getEval("window.moveBy(-1 * window.screenX, 0); window.resizeTo(screen.width,screen.height);");
        
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
        
        sleep(10);
    }
}