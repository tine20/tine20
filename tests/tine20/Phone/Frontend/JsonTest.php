<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Phone_Frontend_Json
 */
class Phone_Frontend_JsonTest extends Phone_AbstractTest
{
    /**
     * Backend
     *
     * @var Phone_Frontend_Json
     */
    protected $_json;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_json = new Phone_Frontend_Json();
    }

    /**
     * try to get all calls
     */
    public function testGetCalls()
    {
        // search calls without phone_id filter -> at least one call will be returned
        $result = $this->_json->searchCalls($this->_objects['filter1'], $this->_objects['paging']);
        $this->assertGreaterThanOrEqual(1, $result['totalcount']);
        $this->assertLessThanOrEqual(3, $result['totalcount']);
        
        // search query -> '05036' -> the user has made 2 calls each with another phone, another made one call, 1 is correct then
        $result = $this->_json->searchCalls($this->_objects['filter2'], $this->_objects['paging']);
        $this->assertEquals(1, $result['totalcount'], 'query filter not working');
        
        $result = $this->_json->searchCalls($this->_objects['filter2b'], $this->_objects['paging']);
        $this->assertEquals(1, $result['totalcount'], 'destination filter not working');
        
        // search for phone_id
        $result = $this->_json->searchCalls($this->_objects['filter3'], $this->_objects['paging']);
        $this->assertGreaterThan(1, $result['totalcount'], 'phone_id filter not working');
        
        $result = $this->_json->searchCalls($this->_objects['filter4'], $this->_objects['paging']);
        // the user searches for a phone not belonging to him, so no results will be returned
        $this->assertEquals(0, $result['totalcount'], 'calls of another user must not be found!');
        
        $result = $this->_json->searchCalls($this->_objects['filter2a'], $this->_objects['paging']);
        $this->assertEquals($this->_objects['phone1']->getId(), $result['results'][0]['phone_id']['id']);
        $this->assertEquals(1, $result['totalcount'], 'the user made one call with this phone!');
        $this->assertEquals($this->_objects['phone1']->getId(), $result['results'][0]['phone_id']['id']);
        $result = $this->_json->searchCalls($this->_objects['filter5'], $this->_objects['paging']);
        
        $this->assertEquals(0, $result['totalcount'], 'calls of another user must not be found!');
        $this->assertEquals('998877', $result['filter'][0]['value'], 'the filter should stay!');
    }

    /**
     * #8380: write a test for saveMyPhone as unprivileged user
     * https://forge.tine20.org/mantisbt/view.php?id=8380
     */
    public function testSaveMyPhoneAsUnprivilegedUser()
    {
        // first save the phone as privileged user
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());
        $userPhone['lines'][0]['asteriskline_id']['cfi_mode'] = "number";
        $userPhone['lines'][0]['asteriskline_id']['cfi_number'] = "+494949302111";
        
        // try to set a property which should be overwritten again
        $userPhone['description'] = 'no phone';
        
        $phone = $this->_json->saveMyPhone($userPhone);
        
        $this->assertEquals('number', $phone['lines'][0]['asteriskline_id']['cfi_mode']);
        $this->assertEquals('+494949302111', $phone['lines'][0]['asteriskline_id']['cfi_number']);
        $this->assertEquals('user phone', $phone['description']);
        
        $additionalLine = array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'snomphone_id'      => $this->_objects['phone']->getId(),
            'asteriskline_id'   => $this->_objects['sippeer']->getId(),
            'linenumber'        => 2,
            'lineactive'        => 2
        );
        
        // use another user which doesn't have access to the phone
        Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()->getFullUserByLoginName('pwulf'));
        
        $e = new Exception('No Exception has been thrown!');
        
        try {
            $this->_json->saveMyPhone($userPhone);
        } catch (Exception $e) {
            
        }
        
        $this->assertEquals('Tinebase_Exception_AccessDenied', get_class($e));
        
        // try to save with a line removed
        Tinebase_Core::set(Tinebase_Core::USER, $this->_adminUser);
        $snomLineBackend = new Voipmanager_Backend_Snom_Line();
        $snomLineBackend->create(new Voipmanager_Model_Snom_Line($additionalLine));
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());

        unset($userPhone['lines'][1]);
        $this->expectException('Tinebase_Exception_AccessDenied');
        $this->_json->saveMyPhone($userPhone);
    }
    
    /**
     * get and update user phone
     * 
     * @return void
     */
    public function testGetUpdateSnomPhone()
    {
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());
        
        $this->assertEquals('user phone', $userPhone['description'], 'no description');
        $this->assertTrue((isset($userPhone['web_language']) || array_key_exists('web_language', $userPhone)), 'missing web_language:' . print_r($userPhone, TRUE));
        $this->assertEquals('English', $userPhone['web_language'], 'wrong web_language');
        $this->assertGreaterThan(0, count($userPhone['lines']), 'no lines attached');

        // update phone
        $userPhone['web_language'] = 'Deutsch';
        $userPhone['lines'][0]['idletext'] = 'idle';
        $userPhone['lines'][0]['asteriskline_id']['cfd_time'] = 60;
        $userPhoneUpdated = $this->_json->saveMyPhone($userPhone);
        
        $this->assertEquals('Deutsch', $userPhoneUpdated['web_language'], 'no updated web_language');
        $this->assertEquals('idle', $userPhoneUpdated['lines'][0]['idletext'], 'no updated idletext');
        $this->assertEquals(60, $userPhoneUpdated['lines'][0]['asteriskline_id']['cfd_time'], 'no updated cfd time');
    }
    
    /**
     * try to get registry data
     */
    public function testGetRegistryData()
    {
        // get phone json
        $data = $this->_json->getRegistryData();
        
        $this->assertGreaterThan(0, count($data['Phones']), 'more than 1 phone expected');
        $this->assertGreaterThan(0, count($data['Phones'][0]['lines']), 'no lines attached');
        $this->assertStringEndsWith('user phone', $data['Phones'][0]['description'], 'no description');
    }
    
    // TODO we need some mocks for asterisk backends...
    public function _testDialNumber()
    {
        $number = '+494031703167';
        $phoneId = $this->_objects['phone']->getId();
        $lineId = $this->_objects['line']->getId();
        
        $status = $this->_json->dialNumber($number, $phoneId, $lineId);
        
        $this->assertEquals('array', gettype($status));
        $this->assertTrue((isset($status['success']) || array_key_exists('success', $status)));
        $this->assertTrue($status['success']);
    }

    /**
     * @see 0011934: show contacts in phone call grid
     */
    public function testContactId()
    {
        // search phone 2 calls (on should be linked to sclever)
        $result = $this->_json->searchCalls($this->_objects['filter3'], $this->_objects['paging']);

        $scleverCall = null;
        foreach ($result['results'] as $call) {
            if ($call['id'] == 'phpunitcallhistoryid4') {
                $scleverCall = $call;
            }
        }

        $this->assertTrue($scleverCall !== null);
        $this->assertTrue(isset($scleverCall['contact_id']));
        $this->assertEquals($this->_personas['sclever']->getId(), $scleverCall['contact_id']['account_id'], print_r($scleverCall['contact_id'], true));
    }
}
