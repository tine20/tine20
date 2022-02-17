<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * this class represents a key field record of a key field config
 * @see http://wiki.tine20.org/Developers/Concepts/KeyFields
 * 
 * @package     Tinebase
 * @subpackage  Config
 *
 * @property    string  $value
 * @property    string  $id
 * @property    boolean $system
 */
class Tinebase_Config_KeyFieldRecord extends Tinebase_Record_Abstract
{
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_identifier
     */
    protected $_identifier = 'id';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_validators
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,         ),
        'created_by'           => array('allowEmpty' => true,         ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
    
        // key field record specific
        'value'                => array('allowEmpty' => false         ),
        'icon'                 => array('allowEmpty' => true          ),
        'color'                => array('allowEmpty' => true          ),
        'system'               => array('allowEmpty' => true          ),
    );
    
    /**
     * allows to add additional validators in subclasses
     * 
     * @var array
     * @see tine20/Tinebase/Record/Abstract::$_validators
     */
    protected $_additionalValidators = array();

    /**
    * Default constructor
    * Constructs an object and sets its record related properties.
    *
    * @param mixed $_data
    * @param bool $_bypassFilters sets {@see this->bypassFilters}
    * @param mixed $_convertDates sets {@see $this->convertDates} and optionaly {@see $this->$dateConversionFormat}
    */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array_merge($this->_validators, $this->_additionalValidators);
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

    public static function getTranslatedValue ($appName, $keyFieldName, $key, $locale = null)
    {
        $config = Tinebase_Config::getAppConfig($appName)->$keyFieldName;
        $keyFieldRecord = $config && $config->records instanceof Tinebase_Record_RecordSet ? $config->records->getById($key) : false;

        if ($locale !== null) {
            $locale = Tinebase_Translation::getLocale($locale);
        }

        $translation = Tinebase_Translation::getTranslation($appName, $locale);
        return $keyFieldRecord ? $translation->translate($keyFieldRecord->value) : $key;
    }

    /** @var Zend_Translate */
    protected static $translation;
    public static function setTranslation(Zend_Translate $translation)
    {
        static::$translation = $translation;
    }

    public function __toString()
    {
        return static::$translation ? static::$translation->getAdapter()->_($this->value) : $this->value;
    }
}
