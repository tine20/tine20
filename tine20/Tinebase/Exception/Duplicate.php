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
     * @var Tinebase_Record_Interface
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
        if (method_exists($_record, 'unsetFieldsBeforeConvertingToJson')) {
            // may need to unset some fields because record is converted to json
            $_record->unsetFieldsBeforeConvertingToJson();
        }

        $this->_clientRecord = $_record;
    }
    
    /**
     * get client record
     * 
     * @return Tinebase_Record_Interface
     */
    public function getClientRecord()
    {
        return $this->_clientRecord;
    }
    
    /**
     * returns existing nodes info as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'code'          => $this->getCode(),
            'message'       => $this->getMessage(),
            'clientRecord'  => $this->_clientRecordToArray(),
            'duplicates'    => $this->_dataToArray(),
        );
    }
    
    /**
     * convert client record to array
     * 
     * @return array
     */
    protected function _clientRecordToArray()
    {
        if (! $this->_clientRecord) {
            return array();
        }
        
        $this->_resolveClientRecordTags();
        $converter = Tinebase_Convert_Factory::factory($this->_clientRecord);
        $result = $converter->fromTine20Model($this->_clientRecord);
        
        return $result;
    }
    
    /**
     * resolve tag ids to tag record
     * 
     * @todo find a generic solution for this!
     */
    protected function _resolveClientRecordTags()
    {
        if (! $this->_clientRecord->has('tags') || empty($this->_clientRecord->tags)) {
            return;
        }
        
        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        foreach ($this->_clientRecord->tags as $tag) {
            if (is_string($tag)) {
                $tag = Tinebase_Tags::getInstance()->get($tag);
            }
            $tags->addRecord($tag);
        }
        $this->_clientRecord->tags = $tags;
    }
}
