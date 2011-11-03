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
            'duplicates'   => $this->_dataToArray(),
        );
    }
    
    /**
     * convert client record to array
     * 
     * @return array
     * 
     * @todo check if model has a specific json converter (use factory?)
     */
    protected function _clientRecordToArray()
    {
        $converter = new Tinebase_Convert_Json();
        $result = $converter->fromTine20Model($this->_clientRecord);
        
        return $result;
    }
}
