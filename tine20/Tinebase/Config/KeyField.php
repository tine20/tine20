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
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::setFromArray()
     */
    public function setFromArray(array $_data)
    {
        if (isset($_data['keyFieldRecords']) && is_array($_data['keyFieldRecords'])) {
            $_data['keyFieldRecords'] = new Tinebase_Record_RecordSet($this->_keyFieldRecordModel, $_data['keyFieldRecords'], TRUE);
        }
        
        parent::setFromArray($_data);
    }
}