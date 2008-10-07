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
        /*
        array(
        'n_family'              => 'Weiss',
        'n_fileas'              => 'Weiss Cornelius',
        'n_given'               => 'Cornelius',
        'org_name'              => 'Metaways Infosystems GmbH',
        'org_unit'              => 'Tine 2.0',
        'adr_one_countryname'   => 'DE',
        'adr_one_locality'      => 'Hamburg',
        'adr_one_postalcode'    => '24xxx',
        'adr_one_region'        => 'Hamburg',
        'adr_one_street'        => 'Pickhuben 4',
        'assistent'             => '',
        'bday'                  => '1979-06-05 03:04:05',
        'email'                 => 'c.weiss@metawyas.de',
        'role'                  => 'Core Developer',
        'title'                 => 'Dipl. Phys.',
    );
*/
    }

    /**
     * tests remote adding of a lead
     *
     */
    public function testAddLead()
    {
        $newLead = $this->_service->addLead(new Crm_Model_Lead($this->_leadData, true));
        $this->assertEquals($this->_leadData['lead_name'], $newLead->lead_name);
        $GLOBALS['Crm_ServiceTest']['newLeadId'] = $newLead->getId();
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
}

if (PHPUnit_MAIN_METHOD == 'Crm_ServiceTest::main') {
    AllTests::main();
}