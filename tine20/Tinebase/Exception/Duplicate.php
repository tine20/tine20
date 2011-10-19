<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Tinebase duplicate exception / error code: 629 (6 => Tinebase, 2 => conflict, 9 => because of 409 for HTTP conflict)
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Duplicate extends Tinebase_Exception_Data
{
    /**
     * the client record
     * 
     * @var Tinebase_Record_Abstract
     */
    protected $_clientRecord = NULL;
    
    /**
     * resolve records / get complete object graph of $_duplicateIds
     * 
     * @var boolean
     */
    protected $_resolveRecords = TRUE;
    
    /**
     * ids of duplicate records
     * 
     * @var unknown_type
     */
    protected $_duplicateIds = array();
    
    /**
     * json frontend for record resolving
     * 
     * @var Tinebase_Frontend_Json_Abstract
     */
    protected $_jsonFrontend = NULL;
    
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'data exception', $_code = 629)
    {
        parent::__construct($_message, $_code);
    }
    
    /**
     * set client record
     * 
     * @param Tinebase_Record_Interface $_record
     */
    public function setClientRecord(Tinebase_Record_Interface $_record)
    {
        $this->_clientRecord = $_record;
    }
    
    /**
     * set duplicate ids
     * 
     * @param array $_ids
     */
    public function setDuplicateIds($_ids)
    {
        $this->_duplicateIds = $_ids;
    }
    
    /**
     * returns existing nodes info as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'code'		   => $this->getCode(),
            'message'	   => $this->getMessage(),
        	'clientRecord' => $this->_clientRecordToArray(),
            'duplicates'   => $this->_duplicatesToArray(),
        );
    }
    
    /**
     * convert client record to array
     * 
     * @return array
     * 
     * @todo use json converter
     */
    protected function _clientRecordToArray()
    {
        if (! $this->_clientRecord) {
            return array();
        }
        
//        if ($this->_resolveRecords) {
//            list($app, $i, $model) = explode('_', $this->_modelName, 3);
//            $method = 'get' . $model;
//            $result = call_user_func_array(array($this->_getJsonFrontend(), $method), array($this->_clientRecord->getId()));
//        } else {
        $this->_clientRecord->setTimezone(Tinebase_Core::get('userTimeZone'));
        $result = $this->_clientRecord->toArray();
        
        return $result;
    }
    
    /**
     * get json frontend
     * 
     * @return Tinebase_Frontend_Json_Abstract
     * @throws Tinebase_Exception
     */
    protected function _getJsonFrontend()
    {
        if (! $this->_jsonFrontend) {
            list($app, $i, $model) = explode('_', $this->_modelName, 3);
            $className = $app . '_Frontend_Json';
            if (! class_exists($className)) {
                throw new Tinebase_Exception('Json frontend does not exist for this model.');
            }
            $this->_jsonFrontend = new $className();
        }
        
        return $this->_jsonFrontend;
    }
    
    /**
     * get duplicates as array
     * 
     * @return array
     */
    protected function _duplicatesToArray()
    {
        if ($this->_resolveRecords) {
            list($app, $i, $model) = explode('_', $this->_modelName, 3);
            $method = 'search' . $model . 's';
            $filter = array(array('field' => 'id', 'operator' => 'in', 'value' => $this->_duplicateIds));
            $result = call_user_func_array(array($this->_getJsonFrontend(), $method), array($filter, array()));
        } else {
            $data = parent::toArray();
            $this->_clientRecord->setTimezone(Tinebase_Core::get('userTimeZone'));
            $result = $data['exceptionData'];
        }
        
        return $result;
    }
}
