<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract implemetation of Tinebase_Record_Interface
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
abstract class Tinebase_Record_Abstract implements Tinebase_Record_Interface
{
    /**
     * ISO8601LONG datetime representation
     */
    const ISO8601LONG = 'Y-m-d H:i:s';
    
    /**
     * should datas be validated on the fly(false) or only on demand(true)
     *
     * @var bool
     */
    public $bypassFilters;
    
    /**
     * should datetimeFields be converted from iso8601 (or optionally others {@see $this->dateConversionFormat}) strings to DateTime objects and back 
     *
     * @var bool
     */
    public $convertDates;
    
    /**
     * differnet format than iso8601 to use for conversions 
     *
     * @var string
     */
    public $dateConversionFormat = NULL;
    
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * NOTE: _Must_ be set by the derived classes!
     * 
     * @var string
     */
    protected $_identifier = NULL;
    
    /**
     * application the record belongs to
     * NOTE: _Must_ be set by the derived classes!
     *
     * @var string
     */
    protected $_application = NULL;
    
    /**
     * holds properties of record
     * 
     * @var array 
     */
    protected $_properties = array();
    
    /**
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend inputfilter
     */
    protected $_filters = array();
    
    /**
     * Defintion of properties. All properties of record _must_ be declared here!
     * This validators get used when validating user generated content with Zend_Input_Filter
     * NOTE: _Must_ be set by the derived classes!
     * 
     * @var array list of zend validator
     */
    protected $_validators = array();
    
    /**
     * the validators place there validation errors in this variable
     * 
     * @var array list of validation errors
     */
    protected $_validationErrors = array();
    
    /**
     * name of fields containing datetime or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array();
    
    /**
     * alarm datetime field
     *
     * @var string
     */
    protected $_alarmDateTimeField = '';
    
    /**
     * name of fields containing time information
     *
     * @var array list of time fields
     */
    protected $_timeFields = array();

    /**
     * name of fields that should be omited from modlog
     *
     * @var array list of modlog omit fields
     */
    protected $_modlogOmitFields = array();
    
    /**
     * name of fields that should not be persisted during create/update in backend
     *
     * @var array
     * 
     * @todo think about checking the setting of readonly field and not allow it
     */
    protected $_readOnlyFields = array();
    
    /**
     * save state if data are validated
     *
     * @var bool
     */
    protected $_isValidated = false;
    
    /**
     * fields to translate when translate() function is called
     *
     * @var array
     */
    protected $_toTranslate = array();
    
    /**
     * array with meta information about the model (like models.js)
     * @var array
     */
    protected static $_meta = NULL;
    
    /**
     * holds instance of Zend_Filters
     *
     * @var array
     */
    protected static $_inputFilters = array();
    
    /**
     * If model is relatable and a special config should be applied, this is configured here
     * @var array
     */
    protected static $_relatableConfig = NULL;

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format: 
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     * 
     * @var array
     */
    protected static $_resolveForeignIdFields = NULL;
    
    /**
     * this property holds all field information for autoboot strapping
     * if this is not null, these properties will be overridden in the abstract constructor:
     *     - _filters
     *     - _validators
     *     - _dateTimeFields
     *     - _alarmDateTimeField
     *     - _timeFields
     *     - _modlogOmitFields
     *     - _readOnlyFields
     *     - _resolveForeignIdFields
     * @var array
     */
    protected static $_fields = NULL;
    
    /**
     * right, user must have to see the module for this model
     */
    protected static $_requiredRight = NULL;
    
    /******************************** functions ****************************************/
    
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     * 
     * @todo what happens if not all properties in the datas are set?
     * The default values must also be set, even if no filtering is done!
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param mixed $convertDates sets {@see $this->convertDates} and optionaly {@see $this->$dateConversionFormat}
     * @return void
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        static::_autoModelingStatic();
        $this->_autoModeling();

        if ($this->_identifier === NULL) {
            throw new Tinebase_Exception_Record_DefinitionFailure('$_identifier is not declared');
        }
        
        $this->bypassFilters = (bool)$_bypassFilters;
        $this->convertDates = (bool)$_convertDates;
        if (is_string($_convertDates)) {
            $this->dateConversionFormat = $_convertDates;
        }

        if ($this->has('description')) {
            $this->_filters['description'] = new Tinebase_Model_InputFilter_CrlfConvert();
        }

        if (is_array($_data)) {
            $this->setFromArray($_data);
        }
    }
    
    /**
     * recursivly clone properties
     */
    public function __clone()
    {
        foreach ($this->_properties as $name => $value)
        {
            if (is_object($value)) {
                $this->_properties[$name] = clone $value;
            } else if (is_array($value)) {
                foreach ($value as $arrKey => $arrValue) {
                    if (is_object($arrValue)) {
                        $value[$arrKey] = clone $arrValue;
                    }
                }
            }
            
            
        }
    }
    
    /**
     * sets identifier of record
     * 
     * @param int identifier
     * @return void
     */
    public function setId($_id)
    {
        // set internal state to "not validated"
        $this->_isValidated = false;
        
        if ($this->bypassFilters === true) {
            $this->_properties[$this->_identifier] = $_id;
        } else {
            $this->__set($this->_identifier, $_id);
        }
    }
    
    /**
     * gets identifier of record
     * 
     * @return int identifier
     */
    public function getId()
    {
        if (! isset($this->_properties[$this->_identifier])) {
            $this->setId(NULL);
        }
        return $this->_properties[$this->_identifier];
    }
    
    /**
     * gets application the records belongs to
     * 
     * @return string application
     */
    public function getApplication()
    {
        return $this->_application;
    }
    
    /**
     * returns id property of this model
     *
     * @return string
     */
    public function getIdProperty()
    {
        return $this->_identifier;
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     * 
     * @todo remove custom fields handling (use Tinebase_Record_RecordSet for them)
     */
    public function setFromArray(array $_data)
    {
        if ($this->convertDates === true) {
            if (! is_string($this->dateConversionFormat)) {
                $this->_convertISO8601ToDateTime($_data);
            } else {
                $this->_convertCustomDateToDateTime($_data, $this->dateConversionFormat);
            }
            
            $this->_convertTime($_data);
        }
        
        // set internal state to "not validated"
        $this->_isValidated = false;

        // get custom fields
        if ($this->has('customfields')) {
            $application = Tinebase_Application::getInstance()->getApplicationByName($this->_application);
            $customFields = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($application, get_class($this))->name;
            $recordCustomFields = array();
        } else {
            $customFields = array();
        }
        
        // make sure we run through the setters
        $bypassFilter = $this->bypassFilters;
        $this->bypassFilters = true;
        foreach ($_data as $key => $value) {
            if (array_key_exists ($key, $this->_validators)) {
                $this->$key = $value;
            } else if (in_array($key, $customFields)) {
                $recordCustomFields[$key] = $value;
            }
        }
        if (!empty($recordCustomFields)) {
            $this->customfields = $recordCustomFields;
        }
        
        $this->bypassFilters = $bypassFilter;
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
    }
    
    /**
     * wrapper for setFromJason which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @todo move this to a generic __call interceptor setFrom<API>InUsersTimezone
     * 
     * @param  string $_data json encoded data
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromJsonInUsersTimezone($_data)
    {
        // change timezone of current php process to usertimezone to let new dates be in the users timezone
        // NOTE: this is neccessary as creating the dates in UTC and just adding/substracting the timeshift would
        //       lead to incorrect results on DST transistions 
        date_default_timezone_set(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));

        // NOTE: setFromArray creates new Tinebase_DateTimes of $this->datetimeFields
        $this->setFromJson($_data);
        
        // convert $this->_datetimeFields into the configured server's timezone (UTC)
        $this->setTimezone('UTC');
        
        // finally reset timzone of current php process to the configured server timezone (UTC)
        date_default_timezone_set('UTC');
    }
    
    /**
     * Sets timezone of $this->_datetimeFields
     * 
     * @see Tinebase_DateTime::setTimezone()
     * @param  string $_timezone
     * @param  bool   $_recursive
     * @return  void
     * @throws Tinebase_Exception_Record_Validation
     */
    public function setTimezone($_timezone, $_recursive = TRUE)
    {
         
        foreach ($this->_datetimeFields as $field) {
            if (!isset($this->_properties[$field])) continue;
            
            if (!is_array($this->_properties[$field])) {
                $toConvert = array($this->_properties[$field]);
            } else {
                $toConvert = $this->_properties[$field];
            }

            
            foreach ($toConvert as $field => &$value) {
                
                if (! method_exists($value, 'setTimezone')) {
                    throw new Tinebase_Exception_Record_Validation($field . 'must be a method setTimezone');
                } 
                $value->setTimezone($_timezone);
            } 
        }
        
        if ($_recursive) {
            foreach ($this->_properties as $property => $value) {
                if (is_object($value) && 
                        (in_array('Tinebase_Record_Interface', class_implements($value)) || 
                        $value instanceof Tinebase_Record_Recordset) ) {
                       
                    $value->setTimezone($_timezone, TRUE);
                }
            }
        }
        
    }
    
    /**
     * returns array of fields with validation errors 
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }
    
    /**
     * returns array with record related properties 
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        /*
        foreach ($this->_properties as $key => $value) {
            if ($value instanceof DateTime) {
                $date = new Tinebase_DateTime($value->get(Tinebase_Record_Abstract::ISO8601LONG));
                $date->setTimezone($value->getTimezone());
                $this->_properties[$key] = $date;
            }
        }
        */
        $recordArray = $this->_properties;
        if ($this->convertDates === true) {
            if (! is_string($this->dateConversionFormat)) {
                $this->_convertDateTimeToString($recordArray, Tinebase_Record_Abstract::ISO8601LONG);
            } else {
                $this->_convertDateTimeToString($recordArray, $this->dateConversionFormat);
            }
        }
        
        if ($_recursive) {
            foreach ($recordArray as $property => $value) {
                if ($this->_hasToArray($value)) {
                    $recordArray[$property] = $value->toArray();
                }
            }
        }
        
        return $recordArray;
    }
    
    /**
     * checks if variable has toArray()
     * 
     * @param mixed $mixed
     * @return boolean
     */
    protected function _hasToArray($mixed)
    {
        return (is_object($mixed) && 
                        (in_array('Tinebase_Record_Interface', class_implements($mixed)) || 
                        $mixed instanceof Tinebase_Record_Recordset) ||
                        (is_object($mixed) && method_exists($mixed, 'toArray')));
    }
    
    /**
     * validate and filter the the internal data
     *
     * @param $_throwExceptionOnInvalidData
     * @return bool
     * @throws Tinebase_Exception_Record_Validation
     */
    public function isValid($_throwExceptionOnInvalidData = false)
    {
        if ($this->_isValidated === false) {
            
            $inputFilter = $this->_getFilter();
            $inputFilter->setData($this->_properties);
            
            if ($inputFilter->isValid()) {
                // set $this->_properties with the filtered values
                $this->_properties = $inputFilter->getUnescaped();
                $this->_isValidated = true;
                
            } else {
                $this->_validationErrors = array();
                
                foreach($inputFilter->getMessages() as $fieldName => $errorMessage) {
                    //print_r($inputFilter->getMessages());
                    $this->_validationErrors[] = array(
                        'id'  => $fieldName,
                        'msg' => $errorMessage
                    );
                }
                if ($_throwExceptionOnInvalidData) {
                    $e = new Tinebase_Exception_Record_Validation('some fields ' . implode(',', array_keys($inputFilter->getMessages())) . ' have invalid content');
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ":\n" .
                        print_r($this->_validationErrors,true). $e);
                    throw $e;
                }
            }
        }
        
        return $this->_isValidated;
    }
    
    /**
     * apply filter
     *
     * @todo implement
     */
    public function applyFilter()
    {
        $this->isValid(true);
        
    }
    
    /**
     * sets record related properties
     * 
     * @param string _name of property
     * @param mixed _value of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    public function __set($_name, $_value)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new Tinebase_Exception_UnexpectedValue($_name . ' is no property of $this->_properties');
        }
        
        $this->_properties[$_name] = $_value;
        $this->_isValidated = false;
        
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
    }
    
    /**
     * unsets record related properties
     * 
     * @param string _name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    public function __unset($_name)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new Tinebase_Exception_UnexpectedValue($_name . ' is no property of $this->_properties');
        }
        
        unset($this->_properties[$_name]);
        
        $this->_isValidated = false;
        
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
    }
    
    /**
     * checkes if an propertiy is set
     * 
     * @param string _name name of property
     * @return bool property is set or not
     */
    public function __isset($_name)
    {
        return isset($this->_properties[$_name]);
    }
    
    /**
     * gets record related properties
     * 
     * @param string _name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return mixed value of property
     */
    public function __get($_name)
    {
        if (!array_key_exists ($_name, $this->_validators)) {
            throw new Tinebase_Exception_UnexpectedValue($_name . ' is no property of $this->_properties');
        }
        
        return array_key_exists($_name, $this->_properties) ? $this->_properties[$_name] : NULL;
    }
    
   /** convert this to string
    *
    * @return string
    */
    public function __toString()
    {
       return print_r($this->toArray(), TRUE);
    }
    
    /**
     * autostarts the model (used in instance)
     */
    protected function _autoModeling()
    {
        if (static::$_meta) {
            if (! $this->_identifier) {
                $this->_identifier = (array_key_exists('idProperty', static::$_meta))  ? static::$_meta['idProperty'] : 'id';
            }
            if (static::$_fields) {
                foreach (static::$_fields as $k => $f) {
                    if (array_key_exists('filters', $f)) {
                        $this->_filters[$k] = $f['filters'];
                    }
                    if (array_key_exists('validators', $f)) {
                        $this->_validators[$k] = $f['validators'];
                    }
                    if (array_key_exists('modlogOmit', $f)) {
                        $this->_modlogOmitFields[] = $k;
                    }
                    if (! array_key_exists('type', $f)) {
                        $f['type'] = 'string';
                    }

                    switch ($f['type']) {
                        case 'string':
                        case 'integer':
                        case 'float':
                        case 'boolean':
                        case 'bool':
                            break;
                        case 'date':
                        case 'datetime':
                            if (array_key_exists('alarm', $f)) {
                                $this->_alarmDateTimeField = $k;
                            }
                            $this->_datetimeFields[] = $k;
                            break;
                        case 'time':
                            $this->_timeFields[] = $k;
                            break;
                        case 'foreign':
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }
    
    /**
     * autostarts the model if _fields are not null
     *     - _filters
     *     - _validators
     *     - _dateTimeFields
     *     - _alarmDateTimeField
     *     - _timeFields
     *     - _modlogOmitFields
     *     - _readOnlyFields
     */
    protected static function _autoModelingStatic()
    {
        if (static::$_meta) {
            if (static::_checkMetaProperty('hasCustomFields')) {
                static::$_fields['customfields'] = array('label' => NULL, 'type' => 'custom', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
            }
            if (static::_checkMetaProperty('hasRelations')) {
                static::$_fields['relations'] = array('label' => NULL, 'type' => 'relation', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
            }
            if (static::_checkMetaProperty('hasTags')) {
                static::$_fields['tags'] = array('label' => 'Tags', 'type' => 'tag', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
            }
            
            if (static::_checkMetaProperty('containerProperty')) {
                static::$_fields[static::$_meta['containerProperty']] = array('label' => 'Container', 'hidden' => true, 'type' => 'container', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
            }
            
            if (static::_checkMetaProperty('useModlog')) {
                // notes are needed if modlog is active
                static::$_fields['notes'] = array('label' => NULL, 'type' => 'note', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
                
                static::$_fields['created_by']         = array('label' => 'Created By', 'type' => 'user', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                static::$_fields['creation_time']      = array('label' => 'Creation Time', 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                static::$_fields['last_modified_by']   = array('label' => 'Last Modified By', 'type' => 'user', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                static::$_fields['last_modified_time'] = array('label' => 'Last Modification Time', 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                // don't show deleted and sequence information
                static::$_fields['deleted_by']         = array('label' => NULL, 'type' => 'user', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                static::$_fields['deleted_time']       = array('label' => NULL, 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                static::$_fields['is_deleted']         = array('label' => NULL, 'type' => 'bool', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));
                static::$_fields['seq']                = array('label' => NULL, 'type' => 'integer', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0));
            } else if (static::_checkMetaProperty('hasNotes')) {
                static::$_fields['notes'] = array('label' => NULL, 'type' => 'note', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
            }
        }
    }
    
    /**
     * helper function to check meta property used in _autoModeling
     * @param string $property
     */
    protected static function _checkMetaProperty($property)
    {
        if (static::$_meta) {
            return array_key_exists($property, static::$_meta) && static::$_meta[$property];
        }
        return false;
    }
    
    /**
     * returns a Zend_Filter for the $_filters and $_validators of this record class.
     * we just create an instance of Filter if we really need it.
     * 
     * @return Zend_Filter_Input
     */
    protected function _getFilter()
    {
        $myClassName = get_class($this);
        
        if (! array_key_exists($myClassName, self::$_inputFilters)) {
            self::$_inputFilters[$myClassName] = new Zend_Filter_Input($this->_filters, $this->_validators);
        }
        return self::$_inputFilters[$myClassName];
    }
    
    /**
     * Converts Tinebase_DateTimes into custom representation
     *
     * @param array &$_toConvert
     * @param string $_format
     * @return void
     */
    protected function _convertDateTimeToString(&$_toConvert, $_format)
    {
        //$_format = "Y-m-d H:i:s";
        foreach ($_toConvert as $field => &$value) {
            if ($value instanceof DateTime) {
                $_toConvert[$field] = $value->format($_format);
            } elseif (is_array($value)) {
                $this->_convertDateTimeToString($value, $_format);
            }
        }
    }
    
    /**
     * Converts iso8601 formated dates into Tinebase_DateTime representation
     * 
     * NOTE: Instead of using the Tinebase_DateTime build in date creation from iso, we 
     *       first convert the dates to UNIX timestamp by hand and create Tinebase_DateTimes
     *       from this timestamp. This brings a 15 times performance boost
     *
     * @param array &$_data
     * 
     * @return void
     */
    protected function _convertISO8601ToDateTime(array &$_data)
    {
        foreach ($this->_datetimeFields as $field) {
            if (!isset($_data[$field]) || $_data[$field] instanceof DateTime) continue;
            
            if (! is_array($_data[$field]) && strpos($_data[$field], ',') !== false) {
                $_data[$field] = explode(',', $_data[$field]);
            }
            
            try {
                if (is_array($_data[$field])) {
                    foreach($_data[$field] as $dataKey => $dataValue) {
                        if ($dataValue instanceof DateTime) continue;
                        $_data[$field][$dataKey] =  (int)$dataValue == 0 ? NULL : new Tinebase_DateTime($dataValue);
                    }
                } else {
                    $_data[$field] = (int)$_data[$field] == 0 ? NULL : new Tinebase_DateTime($_data[$field]);
                    
                }
            } catch (Tinebase_DateTime_Exception $zde) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Error while converting date field "' . $field . '": ' . $zde->getMessage());
                $_data[$field] = NULL;
            }
        }
        
    }
    
    /**
     * Converts custom formated dates into Tinebase_DateTime representation
     *
     * @param array &$_data
     * @param string $_format {@see Tinebase_DateTime}
     * 
     * @return void
     */
    protected function _convertCustomDateToDateTime(array &$_data, $_format)
    {
        foreach ($this->_datetimeFields as $field) {
            if (!isset($_data[$field]) || $_data[$field] instanceof DateTime) continue;
            
            if (strpos($_data[$field], ',') !== false) {
                $_data[$field] = explode(',', $_data[$field]);
            }
            
            if (is_array($_data[$field])) {
                foreach($_data[$field] as $dataKey => $dataValue) {
                    if ($dataValue instanceof DateTime) continue;
                    $_data[$field][$dataKey] =  (int)$dataValue == 0 ? NULL : new Tinebase_DateTime($dataValue);
                }
            } else {
                $_data[$field] = (int)$_data[$field] == 0 ? NULL : new Tinebase_DateTime($_data[$field]);
            }
        }
    }
    
    /**
     * cut the timezone-offset from the iso representation in order to force 
     * Tinebase_DateTime to create dates in the user timezone. otherwise they will be 
     * created with Etc/GMT+<offset> as timezone which would lead to incorrect 
     * results in datetime computations!
     * 
     * @param  string Tinebase_DateTime::ISO8601 representation of a datetime filed
     * @return string ISO8601LONG representation ('Y-m-d H:i:s')
     */
    protected function _convertISO8601ToISO8601LONG($_ISO)
    {
        $cutedISO = preg_replace('/[+\-]{1}\d{2}:\d{2}/', '', $_ISO);
        $cutedISO = str_replace('T', ' ', $cutedISO);
        
        return $cutedISO;
    }
    
    /**
     * Converts time into iso representation (hh:mm:ss)
     *
     * @param array &$_data
     * @return void
     * 
     * @todo    add support for hh:mm:ss AM|PM
     */
    protected function _convertTime(&$_data)
    {
        foreach ($this->_timeFields as $field) {
            if (!isset($_data[$field]) || empty($_data[$field])) {
                continue;
            }
            
            $hhmmss = explode(":", $_data[$field]);
            if (count($hhmmss) === 2) {
                // seconds missing
                $hhmmss[] = '00';
            }
            list($hours, $minutes, $seconds) = $hhmmss;
            if (preg_match('/AM|PM/', $minutes)) {
                list($minutes, $notation) = explode(" ", $minutes);
                switch($notation) {
                    case 'AM':
                        $hours = ($hours == '12') ? 0 : $hours;
                        break;
                    case 'PM':
                        $hours = $hours + 12;
                        break;
                }
                $_data[$field] = implode(':', $hhmmss);
            }
        }
    }
    
    /**
     * returns the default filter group for this model
     * @return string
     */
    protected static function _getDefaultFilterGroup()
    {
        return get_called_class() . 'Filter';
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetExists($_offset)
    {
        return isset($this->_properties[$_offset]);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetGet($_offset)
    {
        return $this->__get($_offset);
    }
    
    /**
     * required by ArrayAccess interface
     */
    public function offsetSet($_offset, $_value)
    {
        return $this->__set($_offset, $_value);
    }
    
    /**
     * required by ArrayAccess interface
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function offsetUnset($_offset)
    {
        throw new Tinebase_Exception_Record_NotAllowed('Unsetting of properties is not allowed');
    }
    
    /**
     * required by IteratorAggregate interface
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_properties);
    }
    
    /**
     * returns a random 40-character hexadecimal number to be used as 
     * universal identifier (UID)
     * 
     * @param int|optional $_length the length of the uid, defaults to 40
     * @return string 40-character hexadecimal number
     */
    public static function generateUID($_length = false)
    {
        $uid = sha1(mt_rand(). microtime());
        
        if ($_length !== false) {
            $uid = substr($uid, 0, $_length);
        }
        
        return $uid;
    }
    
    /**
    * converts a int, string or Tinebase_Record_Interface to a id
    *
    * @param int|string|Tinebase_Record_Abstract $_id the id to convert
    * @param string $_modelName
    * @return int|string
    */
    public static function convertId($_id, $_modelName = 'Tinebase_Record_Abstract')
    {
        if ($_id instanceof $_modelName) {
            if (! $_id->getId()) {
                throw new Tinebase_Exception_InvalidArgument('No id set!');
            }
            $id = $_id->getId();
        } elseif (is_array($_id)) {
            throw new Tinebase_Exception_InvalidArgument('Id can not be an array!');
        } else {
            $id = $_id;
        }
    
        if ($id === 0) {
            throw new Tinebase_Exception_InvalidArgument($_modelName . '.id can not be 0!');
        }
    
        return $id;
    }
    
    /**
     * returns a Tinebase_Record_Diff record with differences to the given record
     * 
     * @param  Tinebase_Record_Interface $_record record for comparison
     * @return Tinebase_Record_Diff|NULL
     */
    public function diff($_record)
    {
        if (! $_record instanceof Tinebase_Record_Abstract) {
            return $_record;
        }
        
        $result = new Tinebase_Record_Diff(array(
            'id'     => $this->getId(),
            'model'  => get_class($_record),
        ));
        $diff = array();
        foreach (array_keys($this->_validators) as $fieldName) {
            $ownField = $this->__get($fieldName);
            $recordField = $_record->$fieldName;
            
            if (in_array($fieldName, $this->_datetimeFields)) {
                if ($ownField instanceof DateTime
                    && $recordField instanceof DateTime) {
                    if ($ownField->compare($recordField) === 0) {
                        continue;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                            ' datetime for field ' . $fieldName . ' is not equal: '
                            . $ownField->getIso() . ' != '
                            . $recordField->getIso()
                        );
                    } 
                } else if (! $recordField instanceof DateTime && $ownField == $recordField) {
                    continue;
                } 
            } else if ($fieldName == $this->_identifier && $this->getId() == $_record->getId()) {
                continue;
            } else if ($ownField instanceof Tinebase_Record_Abstract || $ownField instanceof Tinebase_Record_RecordSet) {
                $subdiv = $ownField->diff($recordField);
                if (is_object($subdiv) && ! $subdiv->isEmpty()) {
                    $diff[$fieldName] = $subdiv;
                }
                continue;
            } else if ($ownField == $recordField) {
                continue;
            } else if (empty($ownField) && empty($recordField)) {
                continue;
            }
            
            $diff[$fieldName] = $recordField;
        }
        
        $result->diff = $diff;
        return $result;
    }
    
    /**
     * check if two records are equal
     * 
     * @param  Tinebase_Record_Interface $_record record for comparism
     * @param  array                     $_toOmit fields to omit
     * @return bool
     */
    public function isEqual($_record, array $_toOmit = array())
    {
        return $this->diff($_record)->isEmpty($_toOmit);
    }
    
    /**
     * translate this records' fields
     *
     */
    public function translate()
    {
        // get translation object
        if (!empty($this->_toTranslate)) {
            $translate = Tinebase_Translation::getTranslation($this->_application);
            
            foreach ($this->_toTranslate as $field) {
                $this->$field = $translate->_($this->$field);
            }
        }
    }

    /**
     * check if the model has a specific field (container_id for example)
     *
     * @param string $_field
     * @return boolean
     */
    public function has($_field) 
    {
        return (array_key_exists ($_field, $this->_validators));
    }   

    /**
     * get fields
     * 
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->_validators);
    }
    
    /**
     * fills a record from json data
     *
     * @param string $_data json encoded data
     * @return void
     * 
     * @todo replace this (and setFromJsonInUsersTimezone) with Tinebase_Convert_Json::toTine20Model
     * @todo move custom _setFromJson to (custom) converter
     */
    public function setFromJson($_data)
    {
        if (is_array($_data)) {
            $recordData = $_data;
        } else {
            $recordData = Zend_Json::decode($_data);
        }
        
        // sanitize container id if it is an array
        if ($this->has('container_id') && isset($recordData['container_id']) && is_array($recordData['container_id']) && isset($recordData['container_id']['id']) ) {
            $recordData['container_id'] = $recordData['container_id']['id'];
        }
        
        $this->_setFromJson($recordData);
        $this->setFromArray($recordData);
    }
    
    /**
     * can be reimplemented by subclasses to modify values during setFromJson
     * @param array $_data the json decoded values
     * @return void
     */
    protected function _setFromJson(array &$_data)
    {
        
    }

    /**
     * returns modlog omit fields
     *
     * @return array
     */
    public function getModlogOmitFields()
    {
        return $this->_modlogOmitFields;
    }

    /**
     * returns read only fields
     *
     * @return array
     */
    public function getReadOnlyFields()
    {
        return $this->_readOnlyFields;
    }

    /**
     * returns the configuration for this model for autostart
     */
    public static function getConfiguration()
    {
        $filterGroup = static::_getDefaultFilterGroup();
        $filterGroup = new $filterGroup();

        // call this method statically
        static::_autoModelingStatic();
        
        return array(
            'filter' => $filterGroup->getFilterModel(),
            'defaultFilter' => $filterGroup->getDefaultFilter(),
            'requiredRight' => static::$_requiredRight,
            'singularContainerMode' => static::$_meta['containerProperty'] ? false : true,
            'fields' => static::$_fields,
            'keys'   => array_keys(static::$_fields),
            'meta'   => static::getMeta()
        );
    }
    
    /**
     * returns meta information about the model
     * @param string $key
     */
    public static function getMeta($key = NULL)
    {
        if (!$key) {
            return static::$_meta;
        } else {
            if (array_key_exists($key, static::$_meta)) {
                return static::$_meta[$key];
            }
            return null;
        }
    }
    
    /**
     * returns the relation config
     * @return array
     */
    public static function getRelatableConfig()
    {
        return static::$_relatableConfig;
    }
    
    /**
     * returns the foreignId fields (used in Tinebase_Convert_Json)
     * @return array
     */
    public static function getResolveForeignIdFields()
    {
        if (static::_checkMetaProperty('useModlog')) {
            $res = array('Tinebase_Model_User' => array('created_by', 'last_modified_by'));
            if (is_array(static::$_resolveForeignIdFields)) {
                return array_merge_recursive(static::$_resolveForeignIdFields, $res);
            } else {
                return $res;
            }
        }
        return static::$_resolveForeignIdFields;
    }
}
