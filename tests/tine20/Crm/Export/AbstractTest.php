<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * abstract Crm_Export class
 */
abstract class Crm_Export_AbstractTest extends Crm_AbstractTest
{
    /**
     * json frontend
     *
     * @var Crm_Frontend_Json
     */
    protected $_json;
    
    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = new Crm_Export_Csv();
        $this->_json = new Crm_Frontend_Json();
        
        $contact = $this->_getContact();
        $task = $this->_getTask();
        $lead = $this->_getLead();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'TASK',    'related_record' => $task->toArray()),
            array('type'  => 'PARTNER', 'related_record' => $contact->toArray()),
        );
        
        $this->_objects['lead'] = $this->_json->saveLead(Zend_Json::encode($leadData));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_json->deleteLeads($this->_objects['lead']['id']);
        Addressbook_Controller_Contact::getInstance()->delete($this->_objects['lead']['relations'][0]['related_id']);        
    }
    
}
