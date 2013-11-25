<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Voipmanager_Backend_Snom_AbstractTest
 */
abstract class Voipmanager_Backend_Snom_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * Fixtures
     * 
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Backend
     *
     * @var Voipmanager_Backend_Snom_Phone
     */
    protected $_backend;

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // we need that because the voip db tables can have a different prefix
        Tinebase_Core::set('voipdbTablePrefix', SQL_TABLE_PREFIX);
        
        $phoneId = Tinebase_Record_Abstract::generateUID();
        
        $this->_objects['location'] = new Voipmanager_Model_Snom_Location(array(
            'id'        => Tinebase_Record_Abstract::generateUID(),
            'name'      => 'phpunit test location',
            'registrar' => 'registrar'
        ));

        $this->_objects['software'] = new Voipmanager_Model_Snom_Software(array(
            'id' => Tinebase_Record_Abstract::generateUID()
        ));
        
        $this->_objects['setting'] = new Voipmanager_Model_Snom_Setting(array(
            'id'           => Tinebase_Record_Abstract::generateUID(),
            'name'         => Tinebase_Record_Abstract::generateUID(),
            'description'  => Tinebase_Record_Abstract::generateUID(),
            'language_w'   => true,
        ));
        
        $this->_objects['phonesettings'] = new Voipmanager_Model_Snom_PhoneSettings(array(
            'phone_id'     => $phoneId,
            'language'     => 'Deutsch',
        ));
        
        $this->_objects['template'] = new Voipmanager_Model_Snom_Template(array(
            'id'          => Tinebase_Record_Abstract::generateUID(),
            'name'        => 'phpunit test location',
            'software_id' => $this->_objects['software']->getId(),
            'setting_id'  => $this->_objects['setting']->getId()
        ));
        
        $this->_objects['phone'] = new Voipmanager_Model_Snom_Phone(array(
            'id'             => $phoneId,
            'macaddress'     => "1234567890cd",
            'location_id'    => $this->_objects['location']->getId(),
            'template_id'    => $this->_objects['template']->getId(),
            'current_model'  => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phoneOwner'] = array(
            'account_id'   => Zend_Registry::get('currentAccount')->getId(),
            'account_type' => 'user'
        );
        
        $rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(
            $this->_objects['phoneOwner']
        ));
        
        $this->_objects['phone']->rights = $rights;
        
        // create phone, location, template
        $snomLocationBackend         = new Voipmanager_Backend_Snom_Location();
        $snomTemplateBackend         = new Voipmanager_Backend_Snom_Template();
        $snomSoftwareBackend         = new Voipmanager_Backend_Snom_Software();
        $snomPhoneBackend            = new Voipmanager_Backend_Snom_Phone();
        $snomSettingBackend          = new Voipmanager_Backend_Snom_Setting();
        $snomPhoneSettingsBackend    = new Voipmanager_Backend_Snom_PhoneSettings();
        
        $snomSoftwareBackend->create($this->_objects['software']);
        $snomLocationBackend->create($this->_objects['location']);
        $snomTemplateBackend->create($this->_objects['template']);
        $snomSettingBackend->create($this->_objects['setting']);
        $snomPhoneBackend->create($this->_objects['phone']);
        $snomPhoneSettingsBackend->create($this->_objects['phonesettings']);
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
}
