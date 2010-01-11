<?php

require_once 'PHPUnit/Framework.php';
set_include_path('.' . PATH_SEPARATOR . '../../../../library' . PATH_SEPARATOR . '../../../..' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

class NtlmTests extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        $this->_auth = new Zend_Auth_Adapter_Http_Ntlm(array(
            'challenge' => '0123456789abcdef',
        ));
        
        // prepare response for testing (phpunit sets headers...)
        $this->_auth->getResponse()->headersSentThrowsException = FALSE;
        
        $targetInfo = array(
            'domain'        => 'DOMAIN',
            'servername'    => 'SERVER',
            'dnsdomain'     => 'domain.com',
            'fqserver'      => 'server.domain.com',
        );
        
        $this->_auth->setTargetInfo($targetInfo);
        
        $resolver = new Zend_Auth_Adapter_Http_Ntlm_Resolver_Mock();
        $this->_auth->setResolver($resolver);
        
        Zend_Session::$_unitTestEnabled = TRUE;
        $session = new Zend_Session_Namespace('ntlm');
        $this->_auth->setSession($session);
        
        $m1hex = '4e544c4d53535000010000000732000006000600330000000b000b0028000000050093080000000f574f524b53544154494f4e444f4d41494e';
        $m1bin = pack('H*', $m1hex);
        
        $authHeader = 'NTLM '. base64_encode($m1bin);
        $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
        
    }
    
    public function testMessage1Decode()
    {
        $authResult = $this->_auth->authenticate();
        
        $this->assertFalse($authResult->isValid(), 'Message 1 must not authenticate');
        
        $identity = $authResult->getIdentity();
        
        // assert flags
        $this->assertTrue($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_NEGOTIATE_UNICODE));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_NEGOTIATE_OEM));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_REQUEST_TARGET));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_NEGOTIATE_NTLM));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_NEGOTIATE_DOMAIN_SUPPLIED));
        $this->assertTrue($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_NEGOTIATE_WORKSTATION_SUPPLIED));
        $this->assertFalse($identity->hasFlag(Zend_Auth_Adapter_Http_Ntlm::FLAG_NEGOTIATE_NTLM2_KEY));
        
        // assert domain / workstatsion
        $this->assertEquals('DOMAIN', $identity->getDomain());
        $this->assertEquals('WORKSTATION', $identity->getWorkstation());
    }
    
    public function testMessage2Encode()
    {
        $authResult = $this->_auth->authenticate();
        
        $headers = $this->_auth->getResponse()->getHeaders();
        $hexMessage2 = bin2hex(base64_decode(substr($headers[0]['value'], 5)));
        
        $m2hex =    "4e544c4d53535000020000000c000c003000000001028100".
                    "0123456789abcdef0000000000000000620062003c000000".
                    "44004f004d00410049004e0002000c0044004f004d004100".
                    "49004e0001000c0053004500520056004500520004001400".
                    "64006f006d00610069006e002e0063006f006d0003002200".
                    "7300650072007600650072002e0064006f006d0061006900".
                    "6e002e0063006f006d0000000000";
        
        $this->assertEquals($m2hex, $hexMessage2);
        
        /*
        echo $m2hex . "\n";
        echo bin2hex(base64_decode(substr($headers[0]['value'], 5))) . "\n";
        */
    }
    
    public function testMessage3Decode()
    {
        // from document LM / NTLM responses
        $m3v1hex =  "4e544c4d5353500003000000180018006a00000018001800".
                    "820000000c000c0040000000080008004c00000016001600".
                    "54000000000000009a0000000102000044004f004d004100".
                    "49004e00750073006500720057004f0052004b0053005400".
                    "4100540049004f004e00c337cd5cbd44fc9782a667af6d42".
                    "7c6de67c20c2d3e77c5625a98c1c31e81847466b29b2df46".
                    "80f39958fb8c213a9cc6";
        
        // captured from IE8 user/SecREt01 
        // with HKEY_LOCAL_MACHINE\System\CurrentControlSet\Control\LSA\LMCompatibility = 3
        // so it should be LMv2 and NTLMv2!
        $m3v2hex =  "4e544c4d5353500003000000180018007e0000008a008a00".
                    "96000000100010004800000008000800580000001e001e00".
                    "600000000000000020010000050280020501280a0000000f".
                    "310030002e0030002e0032002e0032007500730065007200".
                    "4d0045005400410057004100590053002d00300044003000".
                    "3800340031003a7cbeb676aa589be625cbefe881247362b2".
                    "43f24bebcdda433b26e048254c148108da2657b700400101".
                    "0000000000006042ae554d92ca0162b243f24bebcdda0000".
                    "00000200000001000c005300450052005600450052000400".
                    "140064006f006d00610069006e002e0063006f006d000300".
                    "22007300650072007600650072002e0064006f006d006100".
                    "69006e002e0063006f006d00000000000000000000000000";
        
        
        
        $m3bin = pack('H*', $m3v2hex);
        
        $authHeader = 'NTLM '. base64_encode($m3bin);
        $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
        
        $authResult = $this->_auth->authenticate();
        
        $this->assertTrue($authResult->isValid());
        
    }
    
}