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
        'name'              => array('presence' => 'required', 'allowEmpty' => false ),
        'label'             => array('presence' => 'required', 'allowEmpty' => false ),        
        'model'             => array('presence' => 'required', 'allowEmpty' => false ),
        'type'              => array('allowEmpty' => true ),
        'length'            => array('allowEmpty' => true, 'Alnum'  ),
        'group'             => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => '' ),
        'order'             => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0, 'Int' ),
        'account_grants'    => array('allowEmpty' => true ),
    // search for values with typeahead combobox in cf panel
        'value_search'      => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0 ),
    );
    
    /**
     * checks if customfield value is empty
     * 
     * @param string $_value
     * @return boolean
     */
    public function valueIsEmpty($_value)
    {
        if ($this->type == 'checkbox') {
            $result = empty($_value);
        } else {
            $result = ($_value === '' || $_value === NULL);
        }
        
        return $result;
    }
}
