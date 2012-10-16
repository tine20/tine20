<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_json = new Crm_Frontend_Json();
        
        $contact = $this->_getContact();
        $task = $this->_getTask();
        $lead = $this->_getLead();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'TASK',     'related_record' => $task->toArray()),
            array('type'  => 'CUSTOMER', 'related_record' => $contact->toArray()),
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
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
}
