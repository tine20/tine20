<?php
/**
 * Tine 2.0
 * @package     ExampleApplication
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the ExampleApplication application
 *
 * @package     ExampleApplication
 * @subpackage  Frontend
 */
class ExampleApplication_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var ExampleApplication_Controller_ExampleRecord
     */
    protected $_controller = NULL;
    
    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_models = array('ExampleRecord');

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'ExampleApplication';
        $this->_controller = ExampleApplication_Controller_ExampleRecord::getInstance();
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchExampleRecords($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'ExampleApplication_Model_ExampleRecordFilter', TRUE);
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getExampleRecord($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveExampleRecord($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'ExampleRecord');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deleteExampleRecords($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }    

    /**
     * Returns registry data
     * 
     * @return array
     */
    public function getRegistryData()
    {
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer('ExampleApplication_Model_ExampleRecord', NULL, 'defaultExampleRecordContainer')->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();#
        return array(
            'defaultExampleRecordContainer' => $defaultContainerArray
        );
    }
}
