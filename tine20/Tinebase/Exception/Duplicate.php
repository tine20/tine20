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
 * 
 * @todo add phpdoc
 */
class Tinebase_Exception_Duplicate extends Tinebase_Exception_Data
{
    protected $_clientRecord = NULL;
    protected $_resolveRecords = TRUE;
    protected $_duplicateIds = array();
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
            'clientRecord' => $this->_clientRecordToArray(),
            'duplicates'   => $this->_duplicatesToArray(),
        );
    }
    
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
