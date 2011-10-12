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
 * Tinebase duplicate exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Duplicate extends Tinebase_Exception_Data
{
    protected $_clientRecord = NULL;
    
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'data exception', $_code = 520)
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
        $data = parent::toArray();
        
        if ($this->_clientRecord) {
            $this->_clientRecord->setTimezone(Tinebase_Core::get('userTimeZone'));
        }
        
        return array(
            'clientRecord' => $this->_clientRecord->toArray(),
            'duplicates'   => $data['exceptionData'],
        );
    }
}
