<?php /** @noinspection PhpDeprecationInspection */
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * New abstract implementation of Tinebase_Record_Interface
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_NewAbstract extends Tinebase_ModelConfiguration_Const implements Tinebase_Record_Interface
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_NewModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = NULL;

    /**
     * holds data of record
     *
     * @var array
     */
    protected $_data = [];

    /**
     * stores if values got modified after loaded via constructor
     *
     * @var bool
     */
    protected $_isDirty = false;

    /**
     * should data be validated on the fly(false) or only on demand(true)
     *
     * TODO it must not be public!
     *
     * @var bool
     */
    public $bypassFilters = false;

    /**
     * save state if data is validated
     *
     * @var bool
     */
    protected $_isValidated = false;

    /**
     * the validators place their validation errors in this variable
     *
     * @var null|array list of validation errors
     */
    protected $_validationErrors = null;


    /**************** internal statics ****************/
    /**
     * holds instance of Zend_Filters
     * TODO remove this
     * @deprecated
     * @var array
     */
    protected static $_inputFilters = array();

    /**
     * If model is relatable and a special config should be applied, this is configured here
     * TODO remove this
     * @deprecated
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
     * TODO remove this
     * @deprecated
     * @var array
     */
    protected static $_resolveForeignIdFields = NULL;

    /**
     * right, user must have to see the module for this model
     * TODO remove this
     * @deprecated
     */
    protected static $_requiredRight = NULL;

    /**
     * TODO remove this
     * @deprecated
     */
    protected static $_sortExternalMapping = array();


    /******************************** functions ****************************************/

    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * TODO The default values must also be set, even if no filtering is done!
     * TODO really? or only if filtering is done? I think only if filtering is done...?
     *
     * @param array|null $_data
     * @param bool $_bypassFilters sets {@see this->bypassFilters}
     * @param mixed $_convertDates deprecated
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function __construct($_data = null, $_bypassFilters = false, $_convertDates = true)
    {
        // this needs to be instantiated
        if (null === static::$_configurationObject) {
            static::getConfiguration();
        }

        /** @deprecated TODO remove this code */
        if (true !== $_convertDates) {
            throw new Tinebase_Exception_InvalidArgument(static::class . ' doesnt support convertDates anymore');
        }

        $this->bypassFilters = (bool)$_bypassFilters;

        if (is_array($_data)) {
            $this->setFromArray($_data);
        }

        $this->_isDirty = false;
    }

    public function __wakeup()
    {
        // this needs to be instantiated
        if (null === static::$_configurationObject) {
            static::getConfiguration();
        }
    }

    /**
     * returns the configuration object
     *
     * @return Tinebase_NewModelConfiguration
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public static function getConfiguration()
    {
        if (null === static::$_configurationObject) {
            //$modelConfiguration = static::_mergeModelConfiguration();
            static::$_configurationObject = new Tinebase_NewModelConfiguration(static::$_modelConfiguration, static::class);
        }

        return static::$_configurationObject;
    }

    /**
     * TODO this needs improvement, a better recursive merge strategy to allow children to properly overwrite parents
     * TODO maybe look at rocketeer deployment project, there was the same problem
     *
     * @return array
     *
    protected static function _mergeModelConfiguration()
    {
        $modelConfiguration = static::$_modelConfiguration;
        /** @var Tinebase_Record_NewAbstract $parent *
        foreach (class_parents(static::class) as $parent) {
            if (isset($parent::$_modelConfiguration) && null !== $parent::$_modelConfiguration) {
                $modelConfiguration = array_merge_recursive($modelConfiguration, $parent::$_modelConfiguration);
            }
        }

        return $modelConfiguration;
    }*/

    /**
     * resetConfiguration
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public static function resetConfiguration()
    {
        static::$_inputFilters = [];
        static::$_configurationObject = null;
        // we have to re-instantiate it immediately, we depend on it
        static::getConfiguration();

        // TODO ??? Tinebase_ModelConfiguration::resetAvailableApps();
    }

    /**
     * recursively clone properties
     */
    public function __clone()
    {
        foreach ($this->_data as $name => &$value)
        {
            if (is_object($value)) {
                $this->_data[$name] = clone $value;
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
     * sets record related properties
     *
     * @param string $_name of property
     * @param mixed $_value of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_Record_Validation
     */
    public function __set($_name, $_value)
    {
        if (! isset(static::$_configurationObject->_fields[$_name])) {
            throw new Tinebase_Exception_UnexpectedValue($_name . ' is no property of ' . static::class);
        }

        if ($this->bypassFilters !== true) {
            $this->_data[$_name] = $this->_validateField($_name, $_value);
        } else {
            $this->_data[$_name] = $_value;
            $this->_isValidated = false;
        }

        $this->_isDirty = true;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _validateField($name, $value)
    {
        $inputFilter = static::_getFilter($name);
        $inputFilter->setData(array(
            $name => $value
        ));

        if ($inputFilter->isValid()) {
            return $inputFilter->getUnescaped($name);
        }

        $this->_validationErrors = [];

        foreach($inputFilter->getMessages() as $fieldName => $errorMessage) {
            $this->_validationErrors[] = [
                'id'  => $fieldName,
                'msg' => $errorMessage
            ];
        }

        $e = new Tinebase_Exception_Record_Validation('the field ' .
            implode(',', array_keys($inputFilter->getMessages())) . ' has invalid content');
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ":\n" . print_r($this->_validationErrors, true));
        throw $e;
    }

    /**
     * returns a Zend_Filter for the $_filters and $_validators of this record class.
     * we just create an instance of Filter if we really need it.
     *
     * @param string $field
     * @return Zend_Filter_Input
     */
    protected static function _getFilter($field = null)
    {
        $keyName = static::class . $field;

        if (! (isset(self::$_inputFilters[$keyName]) || array_key_exists($keyName, self::$_inputFilters))) {
            $filters = static::$_configurationObject->filters;
            $validators = static::$_configurationObject->validators;
            if ($field !== null) {
                $filters    = isset($filters[$field]) ? [$field => $filters[$field]] : [];
                $validators = [$field => $validators[$field]];

                self::$_inputFilters[$keyName] = new Zend_Filter_Input($filters, $validators);
            } else {
                self::$_inputFilters[$keyName] = new Zend_Filter_Input($filters, $validators);
            }
            self::$_inputFilters[$keyName]->addValidatorPrefixPath('', dirname(dirname(__DIR__)));
        }

        return self::$_inputFilters[$keyName];
    }

    /**
     * sets identifier of record
     *
     * @param string $_id
     * @throws Tinebase_Exception_Record_Validation
     */
    public function setId($_id)
    {
        $this->__set(static::$_configurationObject->getIdProperty(), $_id);
    }

    /**
     * gets identifier of record
     *
     * @return string identifier
     */
    public function getId()
    {
        return $this->__get(static::$_configurationObject->getIdProperty());
    }

    /**
     * gets application the records belongs to
     *
     * @return string application
     */
    public function getApplication()
    {
        return static::$_configurationObject->getAppName();
    }

    /**
     * returns id property of this model
     *
     * @return string
     */
    public function getIdProperty()
    {
        return static::$_configurationObject->getIdProperty();
    }

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data the new data to set
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     * @todo remove custom fields handling (use Tinebase_Record_RecordSet for them)
     */
    public function setFromArray(array &$_data)
    {
        $this->_convertISO8601ToDateTime($_data);

        // set internal state to "not validated"
        $this->_isValidated = false;

        // get custom fields
        if (isset(static::$_configurationObject->_fields['customfields'])) {
            $application = Tinebase_Application::getInstance()->getApplicationByName($this->getApplication());
            $customFields = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($application,
                static::class)->name;
            $recordCustomFields = [];
        } else {
            $customFields = [];
        }

        // make sure we run through the setters
        $bypassFilter = $this->bypassFilters;
        $this->bypassFilters = true;
        foreach ($_data as $key => $value) {
            if (isset(static::$_configurationObject->_fields[$key])) {
                $this->$key = $value;
            } elseif (in_array($key, $customFields)) {
                $recordCustomFields[$key] = $value;
            }
        }
        if (!empty($recordCustomFields)) {
            $this->customfields = $recordCustomFields;
        }

        // convert data to record(s)
        foreach(static::$_configurationObject->_fields as $fieldName => $config) {
            if (isset($_data[$fieldName]) && is_array($_data[$fieldName])) {
                $config = $config['type'] === 'virtual' && isset($config['config']['type']) ? $config['config'] :
                    $config;
                if (in_array($config['type'], ['record', 'records']) && isset($config['config']['appName']) &&
                    isset($config['config']['modelName'])) {
                    $modelName = $config['config']['appName'] . '_Model_' . $config['config']['modelName'];
                    $this->{$fieldName} = $config['type'] == 'record' ?
                        new $modelName($_data[$fieldName], $this->bypassFilters, true) :
                        new Tinebase_Record_RecordSet($modelName, $_data[$fieldName], $this->bypassFilters, true);
                    $this->{$fieldName}->runConvertToRecord();
                }
            }
        }

        $this->bypassFilters = $bypassFilter;
        if ($this->bypassFilters !== true) {
            $this->isValid(true);
        }
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
            ->setData($this->_data);

        if ($inputFilter->isValid()) {
            // set $this->_data with the filtered values
            $this->_data  = $inputFilter->getUnescaped();
            $this->_isValidated = true;

            return true;
        }

        $this->_validationErrors = [];
        foreach ($inputFilter->getMessages() as $fieldName => $errorMessage) {
            $this->_validationErrors[] = [
                'id'  => $fieldName,
                'msg' => $errorMessage
            ];
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
     * returns array of fields with validation errors
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors ?: [];
    }

    /**
     * returns array with record related properties
     *
     * @param boolean $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $recordArray = $this->_data;
        $this->_convertDateTimeToString($recordArray, Tinebase_Record_Abstract::ISO8601LONG);

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
        return $this->_data;
    }


    /**
     * unsets record related properties
     *
     * @param string $_name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_Record_Validation
     */
    public function __unset($_name)
    {
        if (!static::$_configurationObject->hasField($_name)) {
            throw new Tinebase_Exception_UnexpectedValue($_name . ' is no property of ' . static::class);
        }

        unset($this->_data[$_name]);

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
        return isset($this->_data[$_name]);
    }

    /**
     * gets record related properties
     *
     * @param  string  $_name  name of property
     * @return mixed value of property
     */
    public function __get($_name)
    {
        return isset($this->_data[$_name]) ? $this->_data[$_name] : null;
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
     * required by ArrayAccess interface
     *
     * @param mixed $_offset
     * @return boolean
     */
    public function offsetExists($_offset)
    {
        return isset($this->_data[$_offset]);
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
     * @throws Tinebase_Exception_Record_Validation
     */
    public function offsetSet($_offset, $_value)
    {
        $this->__set($_offset, $_value);
    }

    /**
     * required by ArrayAccess interface
     *
     * @param mixed $_offset
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_Record_Validation
     */
    public function offsetUnset($_offset)
    {
        $this->__unset($_offset);
    }

    /**
     * required by IteratorAggregate interface
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_data);
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
     * returns a Tinebase_Record_Diff record with differences to the given record
     *
     * @param Tinebase_Record_Interface $_record record for comparison
     * @param array $omitFields omit fields (for example modlog fields)
     * @return Tinebase_Record_Diff|Tinebase_Record_Interface
     *
     * TODO clean up this code!
     * @throws Tinebase_Exception_Date
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function diff($_record, $omitFields = array())
    {
        /** this is very bad, it is because of the subdiff below... maybe it is resolved in the meantime? */
        if (! $_record instanceof Tinebase_Record_Interface) {
            if (!empty($_record)) {
                return $_record;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Did not get Tinebase_Record_Interface, diffing against empty record');
            $model = static::class;
            $_record = new $model(array(), true);
        }

        $result = new Tinebase_Record_Diff(array(
            'id'     => $this->getId(),
            'model'  => static::class,
        ));
        $diff = array();
        $oldData = array();
        foreach (array_keys(static::$_configurationObject->getFields()) as $fieldName) {
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

            if (in_array($fieldName, static::$_configurationObject->datetimeFields)) {
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
            } elseif ($fieldName == static::$_configurationObject->getIdProperty() && $this->getId() == $_record->getId()) {
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
                /** @var Tinebase_Record_Interface $emptyRecord */
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
     * merge given record into $this
     *
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Record_Diff $diff
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Date
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
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
            if (empty($this->{$field})) {
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
        if (get_class($_record) !== static::class) {
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
     * @param  array $_toOmit fields to omit
     * @return bool
     * @throws Tinebase_Exception_Date
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function isEqual($_record, array $_toOmit = [])
    {
        $diff = $this->diff($_record);
        return ($diff) ? $diff->isEmpty($_toOmit) : false;
    }

    /**
     * check if the model has a specific field (container_id for example)
     *
     * @param string $_field
     * @return boolean
     */
    public function has($_field)
    {
        return static::$_configurationObject->hasField($_field);
    }

    /**
     * get fields
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys(static::$_configurationObject->getFields());
    }

    /**
     * returns modlog omit fields
     *
     * @return array
     */
    public function getModlogOmitFields()
    {
        return static::$_configurationObject->modlogOmitFields;
    }

    /**
     * returns read only fields
     *
     * @return array
     */
    public function getReadOnlyFields()
    {
        return static::$_configurationObject->readOnlyFields;
    }

    /**
     * returns the title of the record
     *
     * @return string
     */
    public function getTitle()
    {
        $titleProperty = static::$_configurationObject->titleProperty;

        if (strpos(static::$_configurationObject->titleProperty, '{') !== false) {
            $translation = Tinebase_Translation::getTranslation($this->getApplication());
            $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), $translation);
            $templateString = $translation->translate($titleProperty);
            $template = $twig->getEnvironment()->createTemplate($templateString);
            return $template->render(is_array($this->_data) ? $this->_data : []);
        } else {
            return $this->$titleProperty;
        }
    }

    public static function getRecordName($locale = null)
    {
        // @TODO implement modelConfig version based on record(s)name
        $translation = Tinebase_Translation::getTranslation(preg_replace('/_.*/', '', static::class), $locale);
        return $translation->translate(preg_replace('/.*_/', '', static::class));
    }

    /**
     * returns all textfields having labels for the autocomplete field function
     *
     * @return array
     * @throws Tinebase_Exception_Record_DefinitionFailure
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

    /**
     * @param array $_defintiion
     */
    public static function inheritModelConfigHook(array &$_defintion)
    {
    }

    public function runConvertToRecord()
    {
        $conf = self::getConfiguration();
        if (null === $conf) {
            return;
        }
        foreach ($conf->getConverters() as $key => $converters) {
            if (isset($this->_data[$key])) {
                /** @var Tinebase_Model_Converter_Interface $converter */
                foreach ($converters as $converter) {
                    $this->_data[$key] = $converter->convertToRecord($this, $key, $this->_data[$key]);
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
            if (isset($this->_data[$key])) {
                /** @var Tinebase_Model_Converter_Interface $converter */
                foreach ($converters as $converter) {
                    $this->_data[$key] = $converter->convertToData($this, $key, $this->_data[$key]);
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
     * @throws Tinebase_Exception_InvalidArgument
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
            } elseif (in_array($property, static::$_configurationObject->datetimeFields) && ! is_object($oldValue)) {
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
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
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
                if (in_array($property, static::$_configurationObject->datetimeFields) && ! is_object($oldValue)) {
                    $oldValue = new Tinebase_DateTime($oldValue);
                }
                $this->$property = $oldValue;
            }
        }
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
        $delegate = Tinebase_Core::getDelegate($this->getApplication(), 'getPathPartDelegate_' . get_called_class() ,
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

        return $parentType . '/{' . static::class . '}' . $this->getId() . $childType;
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
        $result = array(
            'parents'  => $relations->getRelationsOfRecordByDegree($this, Tinebase_Model_Relation::DEGREE_PARENT, true)->asArray(),
            'children' => $relations->getRelationsOfRecordByDegree($this, Tinebase_Model_Relation::DEGREE_CHILD, true)->asArray()
        );

        $this->relations = $oldRelations;
        return $result;
    }

    public function hydrateFromBackend(array &$data)
    {
        // converter below may depend on other record fields, so data needs to be there initially
        foreach ($data as $key => $value) {
            $this->_data[$key] = $value;
        }

        /** @var Tinebase_Model_Converter_Interface $converter */
        foreach (static::$_configurationObject->getConverters() as $key => $converters) {
            if (isset($this->_data[$key])) {
                foreach ($converters as $converter) {
                    $this->_data[$key] = $converter->convertToRecord($this, $key, $this->_data[$key]);
                }
            }
        }
    }

    /**
     * extended properties getter
     *
     * TODO ... this doesn't make the record dirty! very dangerous
     *
     * @param string $_property
     * @return array
     */
    public function &xprops($_property = 'xprops')
    {
        if (!static::$_configurationObject->hasField($_property)) {
            throw new Tinebase_Exception_UnexpectedValue($_property . ' is no property of $this->_properties');
        }
        if (!isset($this->_data[$_property])) {
            $this->_data[$_property] = array();
        } elseif (is_string($this->_data[$_property])) {
            $this->_data[$_property] = json_decode($this->_data[$_property], true);
        }

        return $this->_data[$_property];
    }


    /**
     * extended json data properties getter
     *
     * TODO ... this doesn't make the record dirty! very dangerous
     *
     * @param string $_property
     * @return &array
     */
    public function &jsonData($_property)
    {
        if (!static::$_configurationObject->hasField($_property)) {
            throw new Tinebase_Exception_UnexpectedValue($_property . ' is no property of $this->_properties');
        }
        if (!isset($this->_data[$_property])) {
            $this->_data[$_property] = array();
        } else if (is_string($this->_data[$_property])) {
            $this->_data[$_property] = json_decode($this->_data[$_property], true);
        }

        return $this->_data[$_property];
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
        if (!isset($this->_data[$_property])) {
            return null;
        }

        $value = $this->_data[$_property];
        if (is_object($value) && $value instanceof Tinebase_Record_Interface) {
            return $_getIdFromRecord ? (string)$value->getId() : null;
        } elseif (is_string($value) || is_integer($value)) {
            return (string)$value;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
            ' ' . $_property . '\'s value is neither a record nor an id value: ' . print_r($value, true));
        throw new Tinebase_Exception_UnexpectedValue($_property . '\'s value is neither a record nor an id value');
    }

    /**
     * @return array
     */
    public function getValidators()
    {
        return static::$_configurationObject->validators;
    }

    /**
     * @param array $_validators
     */
    public function setValidators(array $_validators)
    {
        static::$_configurationObject->setValidators($_validators);
    }

    /**
     * @return bool
     */
    public static function generatesPaths()
    {
        return false;
    }

    /**
     * translate this records' fields
     *
     */
    public function translate()
    {
        throw new Tinebase_Exception_NotImplemented(static::class . ' doesn\'t implement translate()');
    }

    /**
     * wrapper for setFromJason which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @param  string $_data json encoded data
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     * @throws Zend_Json_Exception
     * @todo remove this
     * @deprecated
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
     * fills a record from json data
     *
     * @param string|array $_data json encoded data
     * @return void
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Json_Exception
     * @todo remove this
     * @deprecated
     */
    public function setFromJson(&$_data)
    {
        if (is_array($_data)) {
            $recordData = $_data;
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
     *
     * @todo remove this
     * @deprecated
     */
    protected function _setFromJson(array &$_data)
    {

    }

    /**
     * @todo remove this
     * @deprecated
     * @param array $_data
     * @throws Exception
     */
    public function _convertISO8601ToDateTime(array &$_data)
    {
        foreach ([static::$_configurationObject->datetimeFields, static::$_configurationObject->dateFields] as $dtFields) {
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

                if (is_array($value)) {
                    foreach($value as $dataKey => $dataValue) {
                        if ($dataValue instanceof DateTime) {
                            continue;
                        }

                        $value[$dataKey] =  (int)$dataValue == 0 ? NULL : new Tinebase_DateTime($dataValue);
                    }
                } else {
                    $value = (int)$value == 0 ? NULL : new Tinebase_DateTime($value);

                }

                $_data[$field] = $value;
            }
        }
    }

    /**
     * returns the relation config
     *
     * @deprecated
     * @todo remove this
     * @return array
     */
    public static function getRelatableConfig()
    {
        return static::$_relatableConfig;
    }

    /**
     * returns the foreignId fields (used in Tinebase_Convert_Json)
     * @deprecated
     * @todo remove this
     * @return array
     */
    public static function getResolveForeignIdFields()
    {
        return static::$_resolveForeignIdFields;
    }

    /**
     *
     * @deprecated
     * @todo remove this
     * @return array
     */
    public static function getSortExternalMapping()
    {
        return static::$_sortExternalMapping;
    }

    /**
     * Converts Tinebase_DateTimes into custom representation
     *
     * @param array &$_toConvert
     * @param string $_format
     * @deprecated
     * @todo remove this
     */
    protected function _convertDateTimeToString(&$_toConvert, $_format)
    {
        //$_format = "Y-m-d H:i:s";
        foreach ($_toConvert as $field => $value) {
            if (! $value) {
                $dateTimeFields = static::$_configurationObject->datetimeFields;
                if ($dateTimeFields && in_array($field, $dateTimeFields)) {
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
     * Sets timezone of $this->_datetimeFields
     *
     * @see Tinebase_DateTime::setTimezone()
     * @param  string $_timezone
     * @param  bool $_recursive
     * @return  void
     * @deprecated
     * todo we should throw an exception
     * todo later we should remove the function
     * @throws Tinebase_Exception_Record_Validation
     */
    public function setTimezone($_timezone, $_recursive = TRUE)
    {
        foreach (static::$_configurationObject->datetimeFields as $field) {
            if (!isset($this->_data[$field])) continue;

            if (!is_array($this->_data[$field])) {
                $toConvert = array($this->_data[$field]);
            } else {
                $toConvert = $this->_data[$field];
            }

            foreach ($toConvert as $convertField => &$value) {
                if (! method_exists($value, 'setTimezone')) {
                    throw new Tinebase_Exception_Record_Validation($convertField . ' must be a method setTimezone');
                }
                $value->setTimezone($_timezone);
            }
        }

        if ($_recursive) {
            foreach ($this->_data as $property => $propValue) {
                if ($propValue && is_object($propValue) &&
                    ($propValue instanceof Tinebase_Record_Interface ||
                        $propValue instanceof Tinebase_Record_RecordSet) ) {

                    $propValue->setTimezone($_timezone, TRUE);
                }
            }
        }
    }

    /**
     * @param boolean $_bool the new value
     * @return boolean the old value
     * @deprecated
     */
    public function setConvertDates($_bool)
    {
        if ($_bool !== true) {
            throw new Tinebase_Exception_NotImplemented(static::class . ' does not support convertDates anymore');
        }
        return true;
    }

    /**
     * @return string
     */
    public function getNotesTranslatedText()
    {
        return $this->getTitle();
    }

    /**
     * can be used to remove fields that can't be converted to json
     *
     * @todo add this to model config (field config) and just loop the fields here?
     * @todo move this to TRInterface + TRAbstract?
     */
    public function unsetFieldsBeforeConvertingToJson()
    {
    }
}
