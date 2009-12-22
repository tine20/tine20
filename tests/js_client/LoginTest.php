<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class LoginTest extends PHPUnit_Extensions_SeleniumTestCase
 {
    
    public function setUp()
    {
        $this->setBrowser('*firefox');
        $this->setBrowserUrl('http://localhost/tt/tine20/');
    }
    
    public function tearDown()
    {
        try {
           $this->selenium->stop();
        } catch (Testing_Selenium_Exception $e) {
            echo $e;
        }
    }
    
    public function testLogin()
    {
        $this->open('http://localhost/tt/tine20/');
        $this->waitForElementPresent('username');
        $this->type('username', 'tine20admin');
        $this->type('password', 'super');
        
        
        
    }
}