<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_ModelConfiguration, using the test class from hr
 */
class Tinebase_ModelConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * tests if the modelconfiguration gets created using the static call
     */
    public function testModelCreation()
    {
        $employeeCObj = HumanResources_Model_Employee::getConfiguration();
        $fields = $employeeCObj->getFields();

        // test modlog field
        $this->assertArrayHasKey('deleted_time', $fields);
        // test supervisor_id field
        $this->assertArrayHasKey('supervisor_id', $fields);
        $this->assertArrayHasKey('notes', $fields);
        $this->assertArrayHasKey('tags', $fields);

        $contractCObj = HumanResources_Model_Contract::getConfiguration();
        $fields = $contractCObj->getFields();

        // test supervisor_id field not existing in contact configuration object
        $this->assertArrayNotHasKey('supervisor_id', $fields);
        $this->assertArrayHasKey('employee_id', $fields);

        // test employee config again, so nothing gets overwritten
        $employeeCObj = HumanResources_Model_Employee::getConfiguration();
        $fields = $employeeCObj->getFields();
        $this->assertArrayHasKey('deleted_time',  $fields);
        $this->assertArrayHasKey('supervisor_id', $fields);
        $this->assertArrayHasKey('id',            $fields);
        
        $account = Tinebase_Core::getUser();

        $employee = new HumanResources_Model_Employee(array(
            'account_id' => $account->getId(),
            'n_family'   => $account->accountLastName,
            'n_given'    => $account->accountFirstName,
        ));
        
        // test record fields
        $modelConfig = $employee::getConfiguration();
        
        $resolveFields = $modelConfig->recordFields;
        $this->assertArrayHasKey('account_id',       $resolveFields);
        $this->assertArrayHasKey('division_id',      $resolveFields);
        $this->assertArrayHasKey('created_by',       $resolveFields);
        $this->assertArrayHasKey('last_modified_by', $resolveFields);
        $this->assertArrayHasKey('deleted_by',       $resolveFields);

        $contact = new Addressbook_Model_Contact(array('n_family' => 'Spencer', 'n_given' => 'Bud'));
        $co = $contact::getConfiguration();
        $this->assertNull($co);
        
        // test the created filter model
        $filterModel = $employee::getConfiguration()->filterModel;
        
        $this->assertArrayHasKey('id',               $filterModel);
        $this->assertArrayHasKey('query',            $filterModel);
        $this->assertArrayHasKey('account_id',       $filterModel);
        $this->assertArrayHasKey('supervisor_id',    $filterModel);
        
        $this->assertArrayHasKey('options',          $filterModel['supervisor_id']);
        $this->assertArrayHasKey('controller',       $filterModel['supervisor_id']['options']);
        $this->assertArrayHasKey('filtergroup',      $filterModel['supervisor_id']['options']);
        
        $this->assertArrayHasKey('division_id',      $filterModel);
        $this->assertArrayHasKey('created_by',       $filterModel);
        $this->assertArrayHasKey('last_modified_by', $filterModel);
        $this->assertArrayHasKey('deleted_by',       $filterModel);
        
        $this->assertArrayNotHasKey('employee_id',    $filterModel);
        
        $this->assertEquals('Tinebase_Model_Filter_Query', $filterModel['query']['filter']);
    }

    /**
     * tests if the modelconfiguration gets created using the instance call
     */
    public function testModelCreationByInstance()
    {
        // test instance
        $account = Tinebase_Core::getUser();

        $employee = new HumanResources_Model_Employee(array(
            'account_id' => $account->getId(),
            'n_family' => $account->accountLastName,
            'n_given' => $account->accountFirstName,
        ));

        $employeeCObj = $employee::getConfiguration();
        $fields = $employeeCObj->getFields();
        $this->assertArrayHasKey('deleted_time', $fields);
        $this->assertArrayHasKey('supervisor_id', $fields);
        
        // test if sortable is set to false on tags-field
        $this->assertArrayHasKey('sortable', $fields['tags']);
        $this->assertFalse($fields['tags']['sortable']);
        
        $this->assertEquals($account->accountLastName, $employee->n_family);
        $this->assertEquals($account->accountFirstName, $employee->n_given);
    }

    /**
     * tests if the modelconfiguration gets created for the traditional models
     */
    public function testModelCreationTraditional()
    {
        $contact = new Addressbook_Model_Contact(array('n_family' => 'Spencer', 'n_given' => 'Bud'));
        $cObj = $contact->getConfiguration();

        // at first this is just null
        $this->assertNull($cObj);
    }
}
