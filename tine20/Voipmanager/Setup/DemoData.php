<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Voipmanager demo data
 *
 * @package     Setup
 */
class Voipmanager_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Voipmanager_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = 'Voipmanager';
    
    /**
     * the phone which will be created
     * 
     * @var Voipmanager_Model_Snom_Phone
     */
    protected $_phone = NULL;

    /**
     * required apps
     * @var array
     */
    protected static $_requiredApplications = array('Admin');
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return Voipmanager_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Setup_DemoData;
        }

        return self::$_instance;
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Voipmanager_Controller_Snom_Phone::getInstance();
        
        $f = new Voipmanager_Model_Snom_PhoneFilter(array(
            array('field' => 'description', 'operator' => 'equals', 'value' => 'Created by Tine 2.0 DEMO DATA'),
        ), 'AND');
        
        return ($c->search($f)->count() > 0) ? true : false;
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $this->_createSnomPhone();
        $this->_createCalls();
    }
    
    protected function _createCalls()
    {
        $lineId = $this->_phone->lines[0]['id'];
        $phoneId = $this->_phone->getId();
        
        $d1 = Tinebase_DateTime::now()->subDay()->setTime(12,00,00);
        $d2 = clone $d1;
        $d2->addSecond(10);
        $d3 = clone $d2;
        $d3->addMinute(20);
        $i = 1;
        
        while ($i<20) {
            $call = array(
                'line_id'      => $lineId,
                'phone_id'     => $phoneId,
                'callerid'     => $i * 100,
                'start'        => $d1,
                'connected'    => $d2,
                'disconnected' => $d3,
                'duration'     => $d3->getTimestamp() - $d2->getTimestamp(),
                'ringing'      => $i,
                'direction'    => ($i%2) ? 'in' : 'out',
                'source'       => '+49123456789',
                'destination'  => '+44567890123',
            );
            Phone_Controller_Call::getInstance()->create(new Phone_Model_Call($call));
            
            $d1->addMinute(20)->subSecond($i);
            $d2->addMinute(20)->addSecond($i);
            $d3->addMinute(20)->addSecond($i*1);;
            
            $i++;
        }
    }
    
    /**
     * create a phone with a line for the current user
     */
    protected function _createSnomPhone()
    {
        $testLocation = $this->_getSnomLocation();
        $returnedLocation = Voipmanager_Controller_Snom_Location::getInstance()->create($testLocation);
        
        $testTemplate = $this->_getSnomTemplate();
        $returnedTemplate = Voipmanager_Controller_Snom_Template::getInstance()->create($testTemplate);
        
        $rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'read_right'    => TRUE,
            'write_right'   => TRUE,
            'dial_right'    => TRUE
        )));
        $sipPeer = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->create($this->_getAsteriskSipPeer());
        $lines = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_Line', array(array(
            'asteriskline_id' => $sipPeer->getId(),
            'linenumber'      => 1,
            'lineactive'      => 1,
            'idletext'        => 'idle'
        )));
        $settings = new Voipmanager_Model_Snom_PhoneSettings(array(
            'web_language' => 'English'
        ));
        
        $this->_phone = Voipmanager_Controller_Snom_Phone::getInstance()->create(new Voipmanager_Model_Snom_Phone(array(
            'description'       => 'Created by Tine 2.0 DEMO DATA',
            'macaddress'        => substr(Tinebase_Record_Abstract::generateUID(), 0, 12),
            'location_id'       => $returnedLocation['id'],
            'template_id'       => $returnedTemplate['id'],
            'current_model'     => 'snom300',
            'redirect_event'    => 'none',
            'http_client_info_sent' => '1',
            'http_client_user'  => Tinebase_Record_Abstract::generateUID(),
            'http_client_pass'  => Tinebase_Record_Abstract::generateUID(),
            'rights'            => $rights,
            'lines'             => $lines,
            'settings'          => $settings,
        )));
    }
    
    /**
     * get snom location data
     *
     * @return array
     */
    protected function _getSnomLocation()
    {
        return new Voipmanager_Model_Snom_Location(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID(),
            'registrar'   => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * get snom phone template
     *
     * @return array
     */
    protected function _getSnomTemplate()
    {
        $testSoftware = $this->_getSnomSoftware();
        $returnedSoftware = Voipmanager_Controller_Snom_Software::getInstance()->create($testSoftware);
        
        $testSetting = $this->_getSnomSetting();
        $returnedSetting = Voipmanager_Controller_Snom_Setting::getInstance()->create($testSetting);
        
        return new Voipmanager_Model_Snom_Template(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'setting_id'  => $returnedSetting['id'],
            'software_id' => $returnedSoftware['id']
        ), TRUE);
    }
    
    /**
     * get snom software data
     *
     * @return array
     */
    protected function _getSnomSoftware()
    {
        return new Voipmanager_Model_Snom_Software(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * get snom settings data
     *
     * @return array
     */
    protected function _getSnomSetting()
    {
        return new Voipmanager_Model_Snom_Setting(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * get asterisk SipPeer data
     *
     * @return Voipmanager_Model_Asterisk_SipPeer
     */
    protected function _getAsteriskSipPeer()
    {
        // create context
        $context = $this->_getAsteriskContext();
        $context = Voipmanager_Controller_Asterisk_Context::getInstance()->create($context);
        
        return new Voipmanager_Model_Asterisk_SipPeer(array(
            'name'       => Tinebase_Record_Abstract::generateUID(),
            'context'    => $context['name'],
            'context_id' => $context['id']
        ), TRUE);
    }
    
    /**
     * get asterisk context data
     *
     * @return Voipmanager_Model_Asterisk_Context
     */
    protected function _getAsteriskContext()
    {
        return new Voipmanager_Model_Asterisk_Context(array(
            'name'         => Tinebase_Record_Abstract::generateUID(),
            'description'  => 'blabla'
        ), TRUE);
    }
}
