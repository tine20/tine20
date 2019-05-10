<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract implemetation of Tinebase_Record_Interface
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
abstract class Tinebase_Record_Abstract extends Tinebase_ModelConfiguration_Const implements Tinebase_Record_Interface
{
    /**
     * ISO8601LONG datetime representation
     */
    const ISO8601LONG = 'Y-m-d H:i:s';
    
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     * 
     * @var array
     */
    protected static $_modelConfiguration = NULL;

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
    protected $convertDates;
    
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
     * stores if values got modified after loaded via constructor
     * 
     * @var bool
     */
    protected $_isDirty = false;
    
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
     * date fields
     * 
     * @var array
     */
    protected $_dateFields = array();
    
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
     * name of fields that should be omitted from modlog
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

    protected static $_sortExternalMapping = array();


    /******************************** functions ****************************************/
    
    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     * 
     * @todo what happens if not all properties in the datas are set?
     * The default values must also be set, even if no filtering is done!
     * 
     * @param mixed $_data
     * @param bool $_bypassFilters sets {@see this->bypassFilters}
     * @param mixed $_convertDates sets {@see $this->convertDates} and optionaly {@see $this->$dateConversionFormat}
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // apply configuration
        $this->_setFromConfigurationObject();
        
        if ($this->_identifier === NULL) {
            throw new Tinebase_Exception_Record_DefinitionFailure('$_identifier is not declared');
        }
        
        $this->bypassFilters = (bool)$_bypassFilters;
        $this->convertDates = (bool)$_convertDates;
        if (is_string($_convertDates)) {
            $this->dateConversionFormat = $_convertDates;
        }

        if ($this->has('description') && (! (isset($this->_filters['description']) || array_key_exists('description', $this->_filters)))) {
            $this->_filters['description'] = new Tinebase_Model_InputFilter_CrlfConvert();
        }

        if (is_array($_data)) {
            $this->setFromArray($_data);
        }
        
        $this->_isDirty = false;
    }
    
    /**
     * returns the configuration object
     *
     * @return Tinebase_ModelConfiguration|NULL
     */
    public static function getConfiguration()
    {
        if (! isset (static::$_modelConfiguration)) {
            return NULL;
        }
        
        if (! static::$_configurationObject) {
            static::$_configurationObject = new Tinebase_ModelConfiguration(static::$_modelConfiguration, static::class);
        }
    
        return static::$_configurationObject;
    }

    /**
     * resetConfiguration
     */
    public static function resetConfiguration()
    {
        static::$_inputFilters = [];
        static::$_configurationObject = null;
        Tinebase_ModelConfiguration::resetAvailableApps();
    }

    /**
     * returns the relation config
     * 
     * @deprecated
     * @return array
     */
    public static function getRelatableConfig()
    {
        return static::$_relatableConfig;
    }
    
    /**
     * recursivly clone properties
     */
    public function __clone()
    {
        foreach ($this->_properties as $name => &$value)
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
     * @param int $_id
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
     * @return string identifier
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
    public function setFromArray(array &$_data)
    {
        if ($this->convertDates === true) {
            $this->_convertISO8601ToDateTime($_data);
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
            if ((isset($this->_validators[$key]) || array_key_exists ($key, $this->_validators))) {
                $this->$key = $value;
            } else if (in_array($key, $customFields)) {
                $recordCustomFields[$key] = $value;
            }
        }
        if (!empty($recordCustomFields)) {
            $this->customfields = $recordCustomFields;
        }

        // convert data to record(s)
        $modelConfiguration = static::getConfiguration();
        if ($modelConfiguration /*&& $modelConfiguration->setRecordsFromArray*/) {
            foreach($modelConfiguration->getFields() as $fieldName => $config) {
                if (isset($_data[$fieldName]) && is_array($_data[$fieldName])) {
                    $config = $config['type'] === 'virtual' && isset($config['config']['type']) ? $config['config'] :
                        $config;
                    if (in_array($config['type'], ['record', 'records']) && isset($config['config']['appName']) &&
                            isset($config['config']['modelName'])) {
                        $modelName = $config['config']['appName'] . '_Model_' . $config['config']['modelName'];
                        $this->{$fieldName} = $config['type'] == 'record' ?
                            new $modelName($_data[$fieldName], $this->bypassFilters, $this->convertDates) :
                            new Tinebase_Record_RecordSet($modelName, $_data[$fieldName], $this->bypassFilters,
                                $this->convertDates);
                        $this->{$fieldName}->runConvertToRecord();
                    }
                }
            }
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
     * @param  string|array $_data json encoded data
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromJsonInUsersTimezone(&$_data)
    {
        // change timezone of current php process to usertimezone to let new dates be in the users timezone
        // NOTE: this is neccessary as creating the dates in UTC and just adding/substracting the timeshift would
        //       lead to incorrect results on DST transistions 
        date_default_timezone_set(Tinebase_Core::getUserTimezone());

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

            foreach ($toConvert as $convertField => &$value) {
                if (! method_exists($value, 'setTimezone')) {
                    throw new Tinebase_Exception_Record_Validation($convertField . ' must be a method setTimezone');
                } 
                $value->setTimezone($_timezone);
            } 
        }
        
        if ($_recursive) {
            foreach ($this->_properties as $property => $propValue) {
                if ($propValue && is_object($propValue) &&
                        (in_array('Tinebase_Record_Interface', class_implements($propValue)) ||
                            $propValue instanceof Tinebase_Record_RecordSet) ) {

                    $propValue->setTimezone($_timezone, TRUE);
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
        $recordArray = $this->_properties;
        if ($this->convertDates === true) {
            if (! is_string($this->dateConversionFormat)) {
                $this->_convertDateTimeToString($recordArray, Tinebase_Record_Abstract::ISO8601LONG);
            } else {
                $this->_convertDateTimeToString($recordArray, $this->dateConversionFormat);
            }
        }
        
        if ($_recursive) {
            /** @var Tinebase_Record_Interface  $value */
            foreach ($recordArray as $property => $value) {
                if (is_object($value) && method_exists($value, 'toArray')) {
                    $recordArray[$property] = $value->toArray();
                }
            }
        }
        
        return $recordArray;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_properties;
    }
    
    /**
     * checks if variable has toArray()
     * 
     * @param mixed $mixed
     * @return boolean
     */
    protected function _hasToArray($mixed)
    {
        return is_object($mixed) && method_exists($mixed, 'toArray');
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
        if ($this->_isValidated === true) {
            return true;
        }
        
        $inputFilter = $this->_getFilter()
            ->setData($this->_properties);
        
        if ($inputFilter->isValid()) {
            // set $this->_properties with the filtered values
            $this->_properties  = $inputFilter->getUnescaped();
            $this->_isValidated = true;
            
            return true;
        }
        
        $this->_validationErrors = array();
        
        foreach ($inputFilter->getMessages() as $fieldName => $errorMessage) {
            $this->_validationErrors[] = array(
                'id'  => $fieldName,
                'msg' => $errorMessage
            );
        }
        
        if ($_throwExceptionOnInvalidData) {
            $e = new Tinebase_Exception_Record_Validation('Some fields ' . implode(',', array_keys($inputFilter->getMessages()))
                . ' have invalid content');
            
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " "
                . $e->getMessage()
                . print_r($this->_validationErrors, true));
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Record: ' . print_r($this->toArray(), true));
            
            throw $e;
        }
        
        return false;
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
     * @param string $_name of property
     * @param mixed $_value of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    public function __set($_name, $_value)
    {
        if (! (isset($this->_validators[$_name]) || array_key_exists ($_name, $this->_validators))) {
            throw new Tinebase_Exception_UnexpectedValue($_name . ' is no property of $this->_properties');
        }
        
        if ($this->bypassFilters !== true) {
            $this->_properties[$_name] = $this->_validateField($_name, $_value);
        } else {
            $this->_properties[$_name] = $_value;
            
            $this->_isValidated = false;
        }
        
        $this->_isDirty = true;
    }
    
    protected function _validateField($name, $value)
    {
        $inputFilter = $this->_getFilter($name);
        $inputFilter->setData(array(
            $name => $value
        ));
        
        if ($inputFilter->isValid()) {
            return $inputFilter->getUnescaped($name);
        }
        
        $this->_validationErrors = array();
        
        foreach($inputFilter->getMessages() as $fieldName => $errorMessage) {
            $this->_validationErrors[] = array(
                'id'  => $fieldName,
                'msg' => $errorMessage
            );
        }
        
        $e = new Tinebase_Exception_Record_Validation('the field ' . implode(',', array_keys($inputFilter->getMessages())) . ' has invalid content');
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ":\n" .
            print_r($this->_validationErrors,true). $e);
        throw $e;
    }
    
    /**
     * unsets record related properties
     * 
     * @param string $_name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    public function __unset($_name)
    {
        if (!(isset($this->_validators[$_name]) || array_key_exists ($_name, $this->_validators))) {
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
     * @param string $_name name of property
     * @return bool property is set or not
     */
    public function __isset($_name)
    {
        return isset($this->_properties[$_name]);
    }
    
    /**
     * gets record related properties
     * 
     * @param  string  $name  name of property
     * @return mixed value of property
     */
    public function __get($name)
    {
        return (isset($this->_properties[$name]) || array_key_exists($name, $this->_properties))
            ? $this->_properties[$name]
            : NULL;
    }
    
   /** convert this to string
    *
    * @return string
    */
    public function __toString()
    {
       return (string) print_r($this->toArray(), true);
    }
    
    /**
     * returns a Zend_Filter for the $_filters and $_validators of this record class.
     * we just create an instance of Filter if we really need it.
     *
     * @param string $field
     * @return Zend_Filter_Input
     */
    protected function _getFilter($field = null)
    {
        $keyName = get_class($this) . $field;
        
        if (! (isset(self::$_inputFilters[$keyName]) || array_key_exists($keyName, self::$_inputFilters))) {
            if ($field !== null) {
                $filters    = (isset($this->_filters[$field]) || array_key_exists($field, $this->_filters)) ? array($field => $this->_filters[$field]) : array();
                $validators = array($field => $this->_validators[$field]); 
                
                self::$_inputFilters[$keyName] = new Zend_Filter_Input($filters, $validators);
            } else {
                self::$_inputFilters[$keyName] = new Zend_Filter_Input($this->_filters, $this->_validators);
            }
            self::$_inputFilters[$keyName]->addValidatorPrefixPath('', dirname(dirname(__DIR__)));
        }
        
        return self::$_inputFilters[$keyName];
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
        foreach ($_toConvert as $field => $value) {
            if (! $value) {
                if (in_array($field, $this->_datetimeFields)) {
                    $_toConvert[$field] = NULL;
                }
            } elseif ($value instanceof DateTime) {
                $_toConvert[$field] = $value->format($_format);
            } elseif (is_array($value)) {
                $this->_convertDateTimeToString($_toConvert[$field], $_format);
            }
        }
    }
    
    /**
     * Converts iso8601 formated dates into Tinebase_DateTime representation
     * 
     * @param array &$_data
     * @return void
     */
    public function _convertISO8601ToDateTime(array &$_data)
    {
        foreach (array($this->_datetimeFields, $this->_dateFields) as $dtFields) {
            foreach ($dtFields as $field) {
                if (!isset($_data[$field])) {
                    continue;
                }
                
                $value = $_data[$field];
                
                if ($value instanceof DateTime) {
                    continue;
                }
                
                if (! is_array($value) && strpos($value, ',') !== false) {
                    $value = explode(',', $value);
                }
                
                try {
                    if (is_array($value)) {
                        foreach($value as $dataKey => $dataValue) {
                            if ($dataValue instanceof DateTime) {
                                continue;
                            }
                            
                            $value[$dataKey] = (int)$dataValue == 0 || is_array($dataValue) ? NULL : new Tinebase_DateTime($dataValue);
                        }
                    } else {
                        $value = (int)$value == 0 || is_array($value) ? NULL : new Tinebase_DateTime($value);
                        
                    }
                } catch (Tinebase_Exception_Date $zde) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Error while converting date field "' . $field . '": ' . $zde->getMessage());
                    $value = NULL;
                }
                
                $_data[$field] = $value;
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
     * returns the default filter group for this model
     * @return string
     */
    protected static function _getDefaultFilterGroup()
    {
        return get_called_class() . 'Filter';
    }
    
    /**
     * required by ArrayAccess interface
     *
     * @param mixed $_offset
     * @return boolean
     */
    public function offsetExists($_offset)
    {
        return isset($this->_properties[$_offset]);
    }
    
    /**
     * required by ArrayAccess interface
     *
     * @param mixed $_offset
     * @return mixed
     */
    public function offsetGet($_offset)
    {
        return $this->__get($_offset);
    }
    
    /**
     * required by ArrayAccess interface
     *
     * @param mixed $_offset
     * @param mixed $_value
     */
    public function offsetSet($_offset, $_value)
    {
        $this->__set($_offset, $_value);
    }
    
    /**
     * required by ArrayAccess interface
     *
     * @param mixed $_offset
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
     * @param int|null $_length the length of the uid, defaults to 40
     * @return string 40-character hexadecimal number
     */
    public static function generateUID($_length = null)
    {
        $uid = sha1(mt_rand() . microtime());
        
        if ($_length && $_length > 0) {
            $uid = substr($uid, 0, $_length);
        }
        
        return $uid;
    }

    /**
     * converts a int, string or Tinebase_Record_Interface to a id
     *
     * @param int|string|Tinebase_Record_Interface $_id the id to convert
     * @param string $_modelName
     * @return int|string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function convertId($_id, $_modelName = 'Tinebase_Record_Abstract')
    {
        if ($_id instanceof $_modelName) {
            /** @var Tinebase_Record_Interface $_id */
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
     * @param Tinebase_Record_Interface $_record record for comparison
     * @param array $omitFields omit fields (for example modlog fields)
     * @return Tinebase_Record_Diff|NULL
     */
    public function diff($_record, $omitFields = array())
    {
        /** this is very bad, it is because of the subdiff below... maybe it is resolved in the meantime? */
        if (! $_record instanceof Tinebase_Record_Interface) {
            if (!empty($_record)) {
                return $_record;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Did not get Tinebase_Record_Abstract, diffing against empty record');
            $model = get_called_class();
            $_record = new $model(array(), true);
        }
        
        $result = new Tinebase_Record_Diff(array(
            'id'     => $this->getId(),
            'model'  => get_class($_record),
        ));
        $diff = array();
        $oldData = array();
        foreach (array_keys($this->_validators) as $fieldName) {
            if (in_array($fieldName, $omitFields)) {
                continue;
            }
            
            $ownField = $this->__get($fieldName);
            $recordField = $_record->$fieldName;

            if ($fieldName == 'customfields' && is_array($ownField) && is_array($recordField)) {
                // special handling for customfields, remove empty customfields from array
                foreach (array_keys($recordField, '', true) as $key) {
                    unset($recordField[$key]);
                }
                foreach (array_keys($ownField, '', true) as $key) {
                    unset($ownField[$key]);
                }
            }

            if (in_array($fieldName, $this->_datetimeFields)) {
                if ($ownField instanceof DateTime
                    && $recordField instanceof DateTime) {

                    /** @var Tinebase_DateTime $recordField */
                    
                    if (! $ownField instanceof Tinebase_DateTime) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                            ' Convert ' . $fieldName .' to Tinebase_DateTime to make sure we have the compare() method');
                        $ownField = new Tinebase_DateTime($ownField);
                    }
                        
                    if ($ownField->compare($recordField) === 0) {
                        continue;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                            ' datetime for field ' . $fieldName . ' is not equal: '
                            . $ownField->getIso() . ' != '
                            . $recordField->getIso()
                        );
                    } 
                } elseif (! $recordField instanceof DateTime && $ownField == $recordField) {
                    continue;
                } 
            } elseif ($fieldName == $this->_identifier && $this->getId() == $_record->getId()) {
                continue;
            } elseif ($ownField instanceof Tinebase_Record_Interface || $ownField instanceof Tinebase_Record_RecordSet) {
                if ($ownField instanceof Tinebase_Record_Interface && is_scalar($recordField)) {
                    // maybe we have the id of the record -> just compare the id
                    if ($ownField->getId() == $recordField) {
                        continue;
                    } else {
                        $ownField = $ownField->getId();
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                        Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                            ' Doing subdiff for field ' . $fieldName);
                    }
                    $subdiff = $ownField->diff($recordField);
                    if (is_object($subdiff) && !$subdiff->isEmpty()) {
                        $diff[$fieldName] = $subdiff;
                        $oldData[$fieldName] = $ownField;
                    }
                    continue;
                }
            } elseif (empty($ownField) && $recordField instanceof Tinebase_Record_Interface) {
                $model = get_class($recordField);
                $emptyRecord = new $model(array(), true);
                $subdiff = $emptyRecord->diff($recordField);
                if (is_object($subdiff) && ! $subdiff->isEmpty()) {
                    $diff[$fieldName] = $subdiff;
                    $oldData[$fieldName] = $ownField;
                }
                continue;
            } elseif (empty($ownField) && $recordField instanceof Tinebase_Record_RecordSet) {
                $emptyRecordSet = new Tinebase_Record_RecordSet($recordField->getRecordClassName(), array());
                $subdiff = $emptyRecordSet->diff($recordField);
                if (is_object($subdiff) && ! $subdiff->isEmpty()) {
                    $diff[$fieldName] = $subdiff;
                    $oldData[$fieldName] = $ownField;
                }
                continue;
            } elseif ($ownField instanceof Tinebase_Model_Filter_FilterGroup || $recordField instanceof Tinebase_Model_Filter_FilterGroup) {
                // TODO add diff() to Tinebase_Model_Filter_FilterGroup?
                // TODO ignore order of filters - currently it matters! sadly, array_diff does not work with multidimensional arrays
                if (is_object($ownField)) {
                    $ownData = json_encode($ownField->toArray());
                } elseif (is_array($ownField)) {
                    $ownData = json_encode($ownField);
                } else {
                    $ownData = $ownField;
                }
                if (is_object($recordField)) {
                    $recordData = json_encode($recordField->toArray());
                } elseif (is_array($recordField)) {
                    $recordData = json_encode($recordField);
                } else {
                    $recordData = $recordField;
                }
                if ($ownData === $recordData) {
                    continue;
                }
            } elseif ($recordField instanceof Tinebase_Record_Interface && is_scalar($ownField)) {
                // maybe we have the id of the record -> just compare the id
                if ($recordField->getId() == $ownField) {
                    continue;
                } else {
                    $recordField = $recordField->getId();
                }
            } elseif ((($ownField === 0 || $ownField === '0') && $recordField !== 0 && $recordField !== '0') ||
                    ($recordField === 0 || $recordField === '0') && $ownField !== 0 && $ownField !== '0') {
                // do nothing, we want to record this diff below
            } elseif ($ownField == $recordField) {
                continue;
            } elseif (empty($ownField) && empty($recordField)) {
                continue;
            } elseif ((empty($ownField)    && $recordField instanceof Tinebase_Record_RecordSet && count($recordField) == 0)
                ||     (empty($recordField) && $ownField    instanceof Tinebase_Record_RecordSet && count($ownField) == 0) )
            {
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                ' Found diff for ' . $fieldName .'(this/other):' . print_r($ownField, true) . '/' . print_r($recordField, true) );
            
            $diff[$fieldName] = $recordField;
            $oldData[$fieldName] = $ownField;
        }
        
        $result->diff = $diff;
        $result->oldData = $oldData;
        return $result;
    }
    
    /**
     * merge given record into $this, only fills so far empty properties with new values from given record
     * note that 0, '0' are not empty, while null, '' are empty
     * 
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Record_Diff $diff
     * @return Tinebase_Record_Interface
     */
    public function merge($record, $diff = null)
    {
        if (! $this->getId()) {
            $this->setId($record->getId());
        }
        
        if ($diff === null) {
            $diff = $this->diff($record);
        }
        
        if ($diff === null || empty($diff->diff)) {
            return $this;
        }
        
        foreach ($diff->diff as $field => $value) {
            if (empty($this->{$field}) && $this->{$field} !== 0 && $this->{$field} !== '0') {
                $this->{$field} = $value;
            }
        }
        
        return $this;
    }
    
    /**
     * check if data got modified
     * 
     * @return boolean
     */
    public function isDirty()
    {
        return $this->_isDirty;
    }

    /**
     * returns TRUE if given record obsoletes this one
     *
     * @param  Tinebase_Record_Interface $_record
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function isObsoletedBy(Tinebase_Record_Interface $_record)
    {
        if (get_class($_record) !== get_class($this)) {
            throw new Tinebase_Exception_InvalidArgument('Records could not be compared');
        } else if ($this->getId() && $_record->getId() !== $this->getId()) {
            throw new Tinebase_Exception_InvalidArgument('Record id mismatch');
        }
        
        if ($this->has('seq') && $_record->seq != $this->seq) {
            return $_record->seq > $this->seq;
        }
        
        return ($this->has('last_modified_time')) ? $_record->last_modified_time > $this->last_modified_time : TRUE;
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
        $diff = $this->diff($_record);
        return ($diff) ? $diff->isEmpty($_toOmit) : FALSE;
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
        return ((isset($this->_validators[$_field]) || array_key_exists ($_field, $this->_validators)));
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
     * @param string|array $_data json encoded data
     * @return void
     * 
     * @todo replace this (and setFromJsonInUsersTimezone) with Tinebase_Convert_Json::toTine20Model
     * @todo move custom _setFromJson to (custom) converter
     */
    public function setFromJson(&$_data)
    {
        if (is_array($_data)) {
            $recordData = &$_data;
        } else {
            $recordData = Zend_Json::decode($_data);
        }

        if ($this->has('image') && !empty($_data['image']) && preg_match('/location=tempFile&id=([a-z0-9]*)/', $_data['image'], $matches)) {
            // add image to attachments
            if (! isset($recordData['attachments'])) {
                $recordData['attachments'] = array();
            }
            $recordData['attachments'][] = array('tempFile' => array('id' => $matches[1]));
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
     * set read only fields
     *
     * @param array $readOnlyFields
     */
    public function setReadOnlyFields($readOnlyFields)
    {
        $this->_readOnlyFields = $readOnlyFields;
    }

    /**
     * sets the non static properties by the created configuration object on instantiation
     */
    protected function _setFromConfigurationObject()
    {
        // set protected, non static properties
        $co = static::getConfiguration();
        if ($co && $mc = $co->toArray()) {
            foreach ($mc as $property => $value) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * returns the title of the record
     * 
     * @return string
     */
    public function getTitle()
    {
        $c = static::getConfiguration();
        
        // TODO: fallback, remove if all models use modelconfiguration
        if (! $c) {
            return $this->has('title') ? $this->title :
                ($this->has('name') ? $this->name : $this->{$this->_identifier});
        }
        
        if (strpos($c->titleProperty, '{') !== false) {
            $translation = Tinebase_Translation::getTranslation($this->getApplication());
            $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), $translation);
            $templateString = $translation->translate($c->titleProperty);
            $template = $twig->getEnvironment()->createTemplate($templateString);
            return $template->render($this->_properties);
        } else {
            return $this->{$c->titleProperty};
        }
    }

    public static function getRecordName($locale = null)
    {
        // @TODO implement modelConfig version based on record(s)name
        $translation = Tinebase_Translation::getTranslation(preg_replace('/_.*/', '', static::class), $locale);
        return $translation->translate(preg_replace('/.*_/', '', static::class));
    }
    /**
     * returns the foreignId fields (used in Tinebase_Convert_Json)
     * @return array
     */
    public static function getResolveForeignIdFields()
    {
        return static::$_resolveForeignIdFields;
    }
    
    /**
     * returns all textfields having labels for the autocomplete field function
     * 
     * @return array
     */
    public static function getAutocompleteFields()
    {
        $keys = array();
        
        foreach (self::getConfiguration()->getFields() as $key => $fieldDef) {
            if ($fieldDef['type'] == 'string' || $fieldDef['type'] == 'stringAutocomplete' || $fieldDef['type'] == 'text') {
                $keys[] = $key;
            }
        }
        
        return $keys;
    }

    public function runConvertToRecord()
    {
        $conf = self::getConfiguration();
        if (null === $conf) {
            return;
        }
        foreach ($conf->getConverters() as $key => $converters) {
            if (isset($this->_properties[$key])) {
                /** @var Tinebase_Model_Converter_Interface $converter */
                foreach ($converters as $converter) {
                    $this->_properties[$key] = $converter->convertToRecord($this, $key, $this->_properties[$key]);
                }
            }
        }
    }

    public function runConvertToData()
    {
        $conf = self::getConfiguration();
        if (null === $conf) {
            return;
        }
        foreach ($conf->getConverters() as $key => $converters) {
            foreach ($converters as $converter) {
                if (isset($this->_properties[$key])) {
                    /** @var Tinebase_Model_Converter_Interface $converter */
                    $this->_properties[$key] = $converter->convertToData($this, $key, $this->_properties[$key]);
                }
            }
        }
    }

    public static function getSimpleModelName($application, $model)
    {
        $appName = is_string($application) ? $application : $application->name;
        return str_replace($appName . '_Model_', '', $model);
    }

    /**
     * undoes the change stored in the diff
     *
     * @param Tinebase_Record_Diff $diff
     * @return void
     */
    public function undo(Tinebase_Record_Diff $diff)
    {
        /* TODO special treatment? for what? how?
         * oldData does not contain RecordSetDiffs. It plainly contains the old data present in the property before it was changed.
         */

        if ($this->has('is_deleted')) {
            $this->is_deleted = 0;
        }

        foreach((array)($diff->oldData) as $property => $oldValue)
        {
            if ('customfields' === $property) {
                if (!is_array($oldValue)) {
                    $oldValue = array();
                }
                if (isset($diff->diff['customfields']) && is_array($diff->diff['customfields'])) {
                    foreach (array_keys($diff->diff['customfields']) as $unSetProperty) {
                        if (!isset($oldValue[$unSetProperty])) {
                            $oldValue[$unSetProperty] = null;
                        }
                    }
                }
            } elseif (in_array($property, $this->_datetimeFields) && ! is_object($oldValue)) {
                if (null !== $oldValue) {
                    if (is_array($oldValue)) {
                        foreach($oldValue as &$value) {
                            $value = new Tinebase_DateTime($value);
                        }
                        unset($value);
                    } else {
                        $oldValue = new Tinebase_DateTime($oldValue);
                    }
                }

                // TODO use modelconf here!!!
            } elseif (is_array($oldValue) && isset($diff->diff[$property]) && is_array($diff->diff[$property]) &&
                    isset($diff->diff[$property]['model']) && isset($diff->diff[$property]['added']) &&
                    in_array($property, ['relations', 'tags', 'alarms', 'attachments', 'notes', 'attendee'])) {
                $model = $diff->diff[$property]['model'];
                if ('attachments' !== $property) {
                    /** @var Tinebase_Record_Interface $instance */
                    $instance = new $model(array(), true);
                    $idProperty = $instance->getIdProperty();
                    foreach ($oldValue as &$value) {
                        $value[$idProperty] = null;
                    }
                    unset($value);
                }
                if (!in_array($property, ['notes', 'relations'])) {
                    $oldValue = new Tinebase_Record_RecordSet($model, $oldValue);
                }
            }
            $this->$property = $oldValue;
        }
    }

    /**
     * applies the change stored in the diff
     *
     * @param Tinebase_Record_Diff $diff
     * @return void
     */
    public function applyDiff(Tinebase_Record_Diff $diff)
    {
        /* TODO special treatment? for what? how? */

        if ($this->has('is_deleted')) {
            $this->is_deleted = 0;
        }

        foreach((array)($diff->diff) as $property => $oldValue)
        {
            if (is_array($oldValue) && count($oldValue) === 4 &&
                    isset($oldValue['model']) && isset($oldValue['added']) &&
                    isset($oldValue['removed']) && isset($oldValue['modified'])) {
                // RecordSetDiff
                $recordSetDiff = new Tinebase_Record_RecordSetDiff($oldValue);

                if (! $this->$property instanceof Tinebase_Record_RecordSet) {
                    $this->$property = new Tinebase_Record_RecordSet($oldValue['model'],
                        is_array($this->$property)?$this->$property:array());
                }

                /** @var Tinebase_Record_Interface $model */
                $model = $recordSetDiff->model;
                if (true !== $model::applyRecordSetDiff($this->$property, $recordSetDiff)) {
                    $this->$property->applyRecordSetDiff($recordSetDiff);
                }
            } else {
                if (in_array($property, $this->_datetimeFields) && ! is_object($oldValue)) {
                    $oldValue = new Tinebase_DateTime($oldValue);
                }
                $this->$property = $oldValue;
            }
        }
    }

    /**
     * @param array $_defintiion
     */
    public static function inheritModelConfigHook(array &$_defintion)
    {
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSet
     * @param Tinebase_Record_RecordSetDiff $_recordSetDiff
     * @return bool
     */
    public static function applyRecordSetDiff(Tinebase_Record_RecordSet $_recordSet, Tinebase_Record_RecordSetDiff $_recordSetDiff)
    {
        return false;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return false;
    }

    /**
     * @param Tinebase_Record_Interface|null $_parent
     * @param Tinebase_Record_Interface|null $_child
     * @return string
     */
    public function getPathPart(Tinebase_Record_Interface $_parent = null, Tinebase_Record_Interface $_child = null)
    {
        /** @var Tinebase_Record_Abstract_GetPathPartDelegatorInterface $delegate */
        $delegate = Tinebase_Core::getDelegate($this->_application, 'getPathPartDelegate_' . get_called_class() ,
                                                'Tinebase_Record_Abstract_GetPathPartDelegatorInterface');
        if (false !== $delegate) {
            return $delegate->getPathPart($this, $_parent, $_child);
        }

        $parentType = null !== $_parent ? $_parent->getTypeForPathPart() : '';
        $childType = null !== $_child ? $_child->getTypeForPathPart() : '';

        return $parentType . '/' . mb_substr(str_replace(array('/', '{', '}'), '', trim($this->getTitle())), 0, 1024) . $childType;
    }

    /**
     * @return string
     */
    public function getTypeForPathPart()
    {
        return '';
    }

    /**
     * @param Tinebase_Record_Interface|null $_parent
     * @param Tinebase_Record_Interface|null $_child
     * @return string
     *
     * TODO use decorators ? or overwrite
     */
    public function getShadowPathPart(Tinebase_Record_Interface $_parent = null, Tinebase_Record_Interface $_child = null)
    {
        $parentType = null !== $_parent ? $_parent->getTypeForPathPart() : '';
        $childType = null !== $_child ? $_child->getTypeForPathPart() : '';

        return $parentType . '/{' . get_class($this) . '}' . $this->getId() . $childType;
    }

    /**
     * returns an array containing the parent neighbours relation objects or record(s) (ids) in the key 'parents'
     * and containing the children neighbours in the key 'children'
     *
     * @return array
     */
    public function getPathNeighbours()
    {
        $oldRelations = $this->relations;
        $this->relations = null;

        $relations = Tinebase_Relations::getInstance();
        $filter = function($relation) {
            /** @var Tinebase_Model_Relation $relation */
            /** @var Tinebase_Record_Interface $model */
            $model = $relation->related_model;
            return $model::generatesPaths();
        };

        $result = array(
            'parents'  => $relations->getRelationsOfRecordByDegree($this, Tinebase_Model_Relation::DEGREE_PARENT, true)
                ->filter($filter)->asArray(),
            'children' => $relations->getRelationsOfRecordByDegree($this, Tinebase_Model_Relation::DEGREE_CHILD, true)
                ->filter($filter)->asArray()
        );

        $this->relations = $oldRelations;
        return $result;
    }

    /**
     * extended properties getter
     *
     * @param string $_property
     * @return array
     */
    public function &xprops($_property = 'xprops')
    {
        if (!isset($this->_validators[$_property])) {
            throw new Tinebase_Exception_UnexpectedValue($_property . ' is no property of $this->_properties');
        }
        if (!isset($this->_properties[$_property])) {
            $this->_properties[$_property] = array();
        } else if (is_string($this->_properties[$_property])) {
            $this->_properties[$_property] = json_decode($this->_properties[$_property], true);
        }

        return $this->_properties[$_property];
    }

    /**
     * extended json data properties getter
     *
     * @param string $_property
     * @return &array
     */
    public function &jsonData($_property)
    {
        if (!isset($this->_validators[$_property])) {
            throw new Tinebase_Exception_UnexpectedValue($_property . ' is no property of $this->_properties');
        }
        if (!isset($this->_properties[$_property])) {
            $this->_properties[$_property] = array();
        } else if (is_string($this->_properties[$_property])) {
            $this->_properties[$_property] = json_decode($this->_properties[$_property], true);
        }

        return $this->_properties[$_property];
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSetOne
     * @param Tinebase_Record_RecordSet $_recordSetTwo
     * @return null|Tinebase_Record_RecordSetDiff
     */
    public static function recordSetDiff(Tinebase_Record_RecordSet $_recordSetOne, Tinebase_Record_RecordSet $_recordSetTwo)
    {
        return null;
    }

    /**
     * @param string $_property
     * @param mixed $_diffValue
     * @param mixed $_oldValue
     * @return null|boolean
     */
    public function resolveConcurrencyUpdate($_property, $_diffValue, $_oldValue)
    {
        return null;
    }

    /**
     * returns the id of a record property
     *
     * @param string $_property
     * @param boolean $_getIdFromRecord default true, returns null if property has a record and value is false
     * @return string|null
     */
    public function getIdFromProperty($_property, $_getIdFromRecord = true)
    {
        if (!isset($this->_properties[$_property])) {
            return null;
        }

        $value = $this->_properties[$_property];
        if (is_object($value) && $value instanceof Tinebase_Record_Interface) {
            return $_getIdFromRecord ? (string)$value->getId() : null;
        } elseif (is_string($value) || is_integer($value)) {
            return (string)$value;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
            ' ' . $_property . '\'s value is neither a record nor an id value: ' . print_r($value, true));
        throw new Tinebase_Exception_UnexpectedValue($_property . '\'s value is neither a record nor an id value');
    }

    public static function getSortExternalMapping()
    {
        return static::$_sortExternalMapping;
    }

    /**
     * @return array
     */
    public function getValidators()
    {
        return $this->_validators;
    }

    /**
     * @param array $_validators
     */
    public function setValidators(array $_validators)
    {
        $this->_validators = $_validators;
    }

    /**
     * @return bool
     */
    public static function generatesPaths()
    {
        return false;
    }

    /**
     * @param boolean $_bool the new value
     * @return boolean the old value
     */
    public function setConvertDates($_bool)
    {
        $oldValue = $this->convertDates;
        $this->convertDates = $_bool;
        return $oldValue;
    }

    public function hydrateFromBackend(array &$_data)
    {
        $this->setFromArray($_data);
        $this->runConvertToRecord();
    }
}
