<?php
/**
 * Tine 2.0
 * @package     ExampleApplication
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 *
 * This class handles all Json requests for the ExampleApplication application
 *
 * @package     ExampleApplication
 * @subpackage  Frontend
 */
class ExampleApplication_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var ExampleApplication_Controller_Record
     */
    protected $_controller = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'ExampleApplication';
        $this->_controller = ExampleApplication_Controller_Record::getInstance();
    }
    
    /************************************** protected helper functions **************************************/
    
    /************************************** public API **************************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchRecords($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'ExampleApplication_Model_RecordFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getRecord($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveRecord($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'Record');        
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteRecords($ids)
    {
        $this->_delete($ids, $this->_controller);
    }    

}
