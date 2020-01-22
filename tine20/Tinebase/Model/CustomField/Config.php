<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_CustomField_Config
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property    string      id
 * @property    string      application_id
 * @property    string      model
 * @property    string      name
 * @property    string      definition
 * @property    string      account_grants
 * @property    string      value
 * @property    string      label
 * @property    boolean     is_system
 */
class Tinebase_Model_CustomField_Config extends Tinebase_Record_Abstract
{
    const DEF_FIELD = 'fieldDef';
    const DEF_HOOK = 'hook';
    const CONTROLLER_HOOKS = 'controllerHooks';

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
        'id'                    => array('allowEmpty' => true ),
        'application_id'        => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'model'                 => array('presence' => 'required', 'allowEmpty' => false ),
        'name'                  => array('presence' => 'required', 'allowEmpty' => false ),
        'definition'            => array('presence' => 'required', 'allowEmpty' => false ),
        'is_system'             => [Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0],
        'account_grants'        => array('allowEmpty' => true ),
        'value'                 => array('allowEmpty' => true ),
        // Set label from definition if extended resolving is enabled
        'label'                 => array('allowEmpty' => true ),
        // fake properties for modlog purpose only
        'created_by'            => array('allowEmpty' => true ),
        'creation_time'         => array('allowEmpty' => true ),
        'last_modified_by'      => array('allowEmpty' => true ),
        'last_modified_time'    => array('allowEmpty' => true ),
    );

    /**
     * list of zend inputfilter
     *
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = [
        'is_system'          => [Zend_Filter_Empty::class => 0],
    ];
    
    /**
     * checks if customfield value is empty
     * 
     * @param string $_value
     * @return boolean
     */
    public function valueIsEmpty($_value)
    {
        if (isset($this->definition['type']) && $this->definition['type'] === 'bool') {
            $result = empty($_value);
        } else {
            $result = ($_value === '' || $_value === NULL);
        }
        
        return $result;
    }
    
    /**
     * @see tine20/Tinebase/Record/Abstract.php::setFromArray()
     * @param array $_data
     * @return array
     */
    public function setFromArray(array &$_data)
    {
        if (isset($_data['definition'])) {
            if (is_string($_data['definition'])) {
                $_data['definition'] = Zend_Json::decode($_data['definition']);
            }

            if (is_array($_data['definition'])) {
                $_data['definition'] = new Tinebase_Config_Struct($_data['definition']);
            }
        }
        
        return parent::setFromArray($_data);
    }

    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param mixed $convertDates sets {@see $this->convertDates} and optionaly {@see $this->$dateConversionFormat}
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_filters = array('name' => new Tinebase_Model_InputFilter_RemoveWhitespace());

        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return true;
    }
}
