<?php

require_once 'PHPUnit/Framework.php';
set_include_path('.' . PATH_SEPARATOR . '../../../library' . PATH_SEPARATOR . '../../..' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

class NtlmTests extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        //$this->_request = new Zend_Controller_Request_Http();
        //$this->_response = new Zend_Controller_Response_Http();
    }
    
    public function testMessage1Decode()
    {
        $m1hex = '4e544c4d53535000010000000732000006000600330000000b000b0028000000050093080000000f574f524b53544154494f4e444f4d41494e';
        $m1bin = pack('H*', $m1hex);
        
        $authHeader = 'NTLM '. base64_encode($m1bin);
        $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
        
        $auth = new Zend_Auth_Http_Ntlm();
        
        // prepare response for testing (phpunit sets headers...)
        $auth->getResponse()->headersSentThrowsException = FALSE;
        
        $authResult = $auth->authenticate();
        
        $this->assertFalse($authResult->isValid(), 'Message 1 must not authenticate');
        
        $identity = $authResult->getIdentity();
        
        // assert flags
        $this->assertTrue($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_NEGOTIATE_UNICODE));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_NEGOTIATE_OEM));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_REQUEST_TARGET));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_NEGOTIATE_NTLM));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_NEGOTIATE_DOMAIN_SUPPLIED));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_NEGOTIATE_WORKSTATION_SUPPLIED));
        $this->assertFalse($identity->hasFlag(Zend_Auth_Http_Ntlm::FLAG_NEGOTIATE_NTLM2_KEY));
        
        
    }
}