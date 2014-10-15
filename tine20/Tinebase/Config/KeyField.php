<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * this class represents a key field config
 * @see http://www.tine20.org/wiki/index.php/Developers/Concepts/KeyFields
 * 
 * @package     Tinebase
 * @subpackage  Config
 */
class Tinebase_Config_KeyField extends Tinebase_Record_Abstract
{
    /**
     * classname of the key fields record model
     * 
     * @var string
     */
    protected $_keyFieldRecordModel = 'Tinebase_Config_KeyFieldRecord';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_identifier
     */
    protected $_identifier = 'name';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_validators
     */
    protected $_validators = array(
        'name'                   => array('allowEmpty' => true),
        'records'                => array('allowEmpty' => true),
        'default'                => array('allowEmpty' => true),
    );
    
    /**
     * create a new instance
     * 
     * @param mixed     $_data
     * @param string    $_keyFieldRecordModel
     * @return          Tinebase_Config_KeyField 
     */
    public static function create($_data, array $_options = array())
    {
        $record = new self();
        if (isset($_options['recordModel'])) {
            $record->setKeyFieldRecordModel($_options['recordModel']);
        }
        
        $record->setFromArray($_data);
        return $record;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::setFromArray()
     */
    public function setFromArray(array $_data)
    {
        if (isset($_data['records']) && is_array($_data['records'])) {
            $_data['records'] = new Tinebase_Record_RecordSet($this->_keyFieldRecordModel, $_data['records'], TRUE);
        }
        
        parent::setFromArray($_data);
    }
    
    /**
     * set key field record model
     * 
     * @param  string $_keyFieldRecordModel
     * @return Tinebase_Config_KeyField $this
     */
    public function setKeyFieldRecordModel($_keyFieldRecordModel) {
        $this->_keyFieldRecordModel = $_keyFieldRecordModel;
    }
    
    /**
     * get KeyfieldRecord by value
     *
     * @param string $_value
     * @return array
     */
    public function getKeyfieldRecordByValue($_value) {
        $record = $this->records->filter('value', $_value);
        return $record->getFirstRecord();
    }
    
    /**
     * get default KeyfieldRecord
     *
     * @param string $_value
     * @return array
     */
    public function getKeyfieldDefault() {
        if (!empty($this->default)) {
            $record = $this->records->filter('id', $this->default);
            return $record->getFirstRecord();
        }
        return '';
    }
}