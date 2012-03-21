<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_CustomField_Config
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_CustomField_Config extends Tinebase_Record_Abstract 
{
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => true ),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'model'             => array('presence' => 'required', 'allowEmpty' => false ),
        'name'              => array('presence' => 'required', 'allowEmpty' => false ),
        'definition'        => array('presence' => 'required', 'allowEmpty' => false ),
        'account_grants'    => array('allowEmpty' => true ),       
    );
    
    /**
     * checks if customfield value is empty
     * 
     * @param string $_value
     * @return boolean
     */
    public function valueIsEmpty($_value)
    {
        if ($this->definition['type'] == 'bool') {
            $result = empty($_value);
        } else {
            $result = ($_value === '' || $_value === NULL);
        }
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract.php::setFromArray()
     */
    public function setFromArray(array $_data)
    {
        if (array_key_exists('definition', $_data)) {
            if (is_string($_data['definition'])) {
                $_data['definition'] = Zend_Json::decode($_data['definition']);
            }
            
            if (is_array($_data['definition'])) {
                $_data['definition'] = new Tinebase_Config_Struct($_data['definition']);
            }
        }
        
        return parent::setFromArray($_data);
    }
}
