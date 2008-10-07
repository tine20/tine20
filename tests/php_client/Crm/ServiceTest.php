<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_ServiceTest::main');
}

class Crm_ServiceTest extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;
    
    /**
     * @var Tinebase_Connection
     */
    protected $_connection = NULL;
    
    /**
     * @var Crm_Service
     */
    protected $_service = NULL;
    
    /**
     * @var array
     */
    protected $_leadData = NULL;
    
    /**
     * @var array
     */
    protected $_contactData = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Crm_ServiceTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {
        $this->_connection = Tinebase_Connection::getDefaultConnection();
        $this->_service = new Crm_Service($this->_connection);
        
        $tinebaseService = new Tinebase_Service(($this->_connection));
        $containers = $tinebaseService->getContainer('Crm', 'personal', $this->_connection->getUser()->getId());
        
        $this->_leadData = array(
            'lead_name'     => 'PHPUnit test lead from php_client',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $containers[0]->getId(),
            'start'         => Zend_Date::now(),
            'description'   => 'I want to hire all your tine developers!',
            'end'           => NULL,
            'turnover'      => '15000000',
            'probability'   => 50,
            'end_scheduled' => NULL,
        );
        
        $this->_contactData = array(
            'n_family'              => 'Potential',
            'n_fileas'              => 'Potential Customer',
            'n_given'               => 'Customer',
            'org_name'              => 'We have lots of money ltd.',
            'org_unit'              => 'Strategic Sellings',
            'adr_one_countryname'   => 'US',
            'adr_one_locality'      => 'New York',
            'adr_one_postalcode'    => '2234',
            'adr_one_region'        => 'New York',
            'adr_one_street'        => 'Main Road 1',
            'assistent'             => '',
            'bday'                  => '',
            'email'                 => 'c.potential@wehavemoney.us',
            'role'                  => 'CEO',
            'title'                 => '',
        );
    }

    /**
     * tests remote adding of a lead
     *
     */
    public function testAddLead()
    {
        $lead = new Crm_Model_Lead($this->_leadData, true);
        $lead->relations = array(
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => NULL,
                'own_id'                 => NULL,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_PARENT,
                'related_model'          => 'Addressbook_Model_Contact',
                'related_backend'        => NULL,
                'related_id'             => NULL,
                'type'                   => 'CUSTOMER',
                'related_record'         => $this->_contactData
            )
        );
        $newLead = $this->_service->addLead($lead);
        
        $this->assertEquals($this->_leadData['lead_name'], $newLead->lead_name);
        $GLOBALS['Crm_ServiceTest']['newLeadId'] = $newLead->getId();
        $GLOBALS['Crm_ServiceTest']['newContactId'] = $newLead->relations[0]['related_id'];
    }
    
    /**
     * test remote retrivial of a lead
     *
     */
    public function testGetLead()
    {
        $remoteLead = $this->_service->getLead($GLOBALS['Crm_ServiceTest']['newLeadId']);
        $this->assertEquals($this->_leadData['lead_name'], $remoteLead->lead_name);
    }
    
    /**
     * test remote deleting of a lead
     *
     */
    public function testDeleteLead()
    {
        $this->_service->deleteLead($GLOBALS['Crm_ServiceTest']['newLeadId']);
        $this->setExpectedException('Exception');
        $remoteLead = $this->_service->getLead($GLOBALS['Crm_ServiceTest']['newLeadId']);
    }
    
    /**
     * test to delete related contact
     *
     */
    public function testDeleteContact()
    {
        // cleanup
        $adbService = new Addressbook_Service($this->_connection);
        $adbService->deleteContact($GLOBALS['Crm_ServiceTest']['newContactId']);
    }

}

if (PHPUnit_MAIN_METHOD == 'Crm_ServiceTest::main') {
    AllTests::main();
}