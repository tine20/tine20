<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Crm_Backend_Lead
 */
class Crm_Backend_LeadTest extends PHPUnit_Framework_TestCase
{
    /**
     * Testcontainer
     *
     * @var Tinebase_Model_Container
     */
    protected $_testContainer;
    
    /**
     * Backend
     *
     * @var Crm_Backend_Lead
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Leads Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_backend = new Crm_Backend_Lead();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Crm', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        if ($personalContainer->count() === 0) {
            $this->_testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->_testContainer = $personalContainer[0];
        }
    }
    
    /**
     * Tears down the fixture
     * 
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * try to add a lead
     * 
     * @return Crm_Model_Lead
     */
    public function testCreateLead()
    {
        $lead = $this->_backend->create(self::getTestLead($this->_testContainer));
        
        $this->assertTrue(!empty($lead->id));
        
        return $lead;
    }
    
    /**
     * try to get a lead
     * 
     * @return Crm_Model_Lead
     */
    public function testGetLead()
    {
        $lead = $this->testCreateLead();
        $updateLead = $this->_backend->get($lead->getId());
        
        $this->assertTrue($updateLead instanceof Crm_Model_Lead);
        $this->assertEquals($lead->getId(), $updateLead->getId());
        $this->assertEquals($lead->description, $updateLead->description);
       
        return $lead;
    }
    
    /**
     * try to get initial lead with search function
     */
    public function testGetInitialLead()
    {
        $lead = $this->testCreateLead();
        
        $filter = $this->_getFilter();
        $leads = $this->_backend->search($filter);
        $this->assertEquals(0, count($leads), 'Closed lead should not be found.');

        $filter = $this->_getFilter(TRUE);
        $leads = $this->_backend->search($filter);
        $this->assertEquals(1, count($leads), 'Closed lead should be found.');
    }
    
    /**
     * try to update a lead
     */
    public function testUpdateLead()
    {
        $lead = $this->testCreateLead();
        
        $lead->description = 'Invalid Description';
        
        $lead = $this->_backend->update($lead);
       
        $this->assertEquals('Invalid Description', $lead->description, 'description mismatch');
        
        return $lead;
    }
    
    /**
     * try to get initial lead with search function
     */
    public function testGetUpdatedLead()
    {
        $this->testUpdateLead();
        
        $filter = $this->_getFilter(TRUE);
        $leads = $this->_backend->search($filter);
        
        $this->assertEquals(1, count($leads));
    }
    
    /**
     * try to get count of leads
     */
    public function testGetCountOfLeads()
    {
        $lead = $this->testCreateLead();
        
        $filter = $this->_getFilter(TRUE);
        
        $count = $this->_backend->searchCount($filter);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to delete a contact
     */
    public function testDeleteLead()
    {
        $lead = $this->testCreateLead();
        
        $this->_backend->delete($lead->getId());
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $lead = $this->_backend->get($lead->getId());
    }

    /**
     * get lead filter
     *
     * @return Crm_Model_LeadFilter
     */
    protected function _getFilter($_showClosed = FALSE)
    {
        return new Crm_Model_LeadFilter(array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'PHPUnit'
            ),
            array(
                'field' => 'container_id', 
                'operator' => 'equals', 
                'value' => $this->_testContainer->id
            ),
            array(
                'field' => 'showClosed', 
                'operator' => 'equals', 
                'value' => $_showClosed
            ),
        ));
    }
    
    /**
     * create test lead
     *
     * @return Crm_Model_Lead
     */
    public static function getTestLead(Tinebase_Model_Container $_testContainer)
    {
        $lead = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => $_testContainer->id,
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description',
            'end'           => Tinebase_DateTime::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Tinebase_DateTime::now(),
        ));
        return $lead;
    }
}
