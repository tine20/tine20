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
 * Tinebase exception with exception data
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Data extends Tinebase_Exception
{
    /**
     * exception data
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_exceptionData = NULL;
    
    /**
     * model name
     * 
     * @var string
     */
    protected $_modelName = NULL;
    
    /**
     * set model name
     * 
     * @param string $_modelName
     */
    public function setModelName($_modelName)
    {
        $this->_modelName = $_modelName;
    }
    
    /**
     * add record to exception data
     * 
     * @param Tinebase_Record_Interface $_record
     */
    public function addRecord(Tinebase_Record_Interface $_existingNode)
    {
        $this->getData()->addRecord($_existingNode);
    }
    
    /**
     * set exception data
     * 
     * @param Tinebase_Record_RecordSet of Tinebase_Record_Interface
     */
    public function setData(Tinebase_Record_RecordSet $_exceptionData)
    {
        $this->_exceptionData = $_exceptionData;
    }
        
    /**
     * get exception data
     * 
     * @return Tinebase_Record_RecordSet of Tinebase_Record_Interface
     */
    public function getData()
    {
        if ($this->_exceptionData === NULL) {
            if (empty($this->_modelName)) {
                throw new Tinebase_Exception_NotFound('modelName not found in class.');
            }
        
            $this->_exceptionData = new Tinebase_Record_RecordSet($this->_modelName);
        }
        
        return $this->_exceptionData;
    }
    
    /**
     * returns existing exception data as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'code'            => $this->getCode(),
            'message'        => $this->getMessage(),
            'exceptionData' => $this->_dataToArray(),
        );
    }
    
    /**
    * get exception data as array
    *
    * @return array
    *
    * @todo check if model has a specific json converter (use factory?)
    */
    protected function _dataToArray()
    {
        $converter = Tinebase_Convert_Factory::factory($this->_modelName);
        $result = $converter->fromTine20RecordSet($this->_exceptionData);
    
        return $result;
    }
}
