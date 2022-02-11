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
 * @see http://wiki.tine20.org/Developers/Concepts/KeyFields
 * 
 * @package     Tinebase
 * @subpackage  Config
 * @property    Tinebase_Record_RecordSet   $records
 * @property    string                      $name
 * @property    mixed                       $default
 */
class Tinebase_Config_KeyField extends Tinebase_Record_Abstract
{
    /**
     * appname this keyfield belongs to
     *
     * @var string
     */
    protected $_appName = null;

    /**
     * classname of the key fields record model
     * 
     * @var string
     */
    protected $_keyFieldRecordModel = 'Tinebase_Config_KeyFieldRecord';

    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::$_identifier
     */
    protected $_identifier = 'name';
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::$_validators
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
     * @param array     $_options
     * @return          Tinebase_Config_KeyField 
     */
    public static function create($_data, array $_options = array())
    {
        $record = new self();
        if (isset($_options['appName'])) {
            $record->setAppName($_options['appName']);
        }
        if (isset($_options['recordModel'])) {
            $record->setKeyFieldRecordModel($_options['recordModel']);
        }

        if (is_array($_data)) {
            $record->setFromArray($_data);
        } else if (is_string($_data)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                . __LINE__ . ' Did not get an array to set keyfield config. Got this: ' . $_data);
        }
        return $record;
    }
    
    /**
     * @param array $_data
     * @see Tinebase_Record_Abstract::setFromArray()
     */
    public function setFromArray(array &$_data)
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
    public function setKeyFieldRecordModel($_keyFieldRecordModel)
    {
        $this->_keyFieldRecordModel = $_keyFieldRecordModel;

        return $this;
    }

    /**
     * set appName
     *
     * @param  string $_appName
     * @return Tinebase_Config_KeyField $this
     */
    public function setAppName($_appName)
    {
        $this->_appName = $_appName;

        return $this;
    }
    
    /**
     * get KeyfieldRecord by value
     *
     * @param string $_value
     * @return Tinebase_Config_KeyFieldRecord
     */
    public function getKeyfieldRecordByValue($_value)
    {
        return $this->records->find('value', $_value);
    }
    
    /**
     * get default KeyfieldRecord
     *
     * @return Tinebase_Config_KeyFieldRecord
     */
    public function getKeyfieldDefault()
    {
        if (!empty($this->default) && $this->records instanceof Tinebase_Record_RecordSet) {
            return $this->records->find('id', $this->default);
        }
        return null;
    }

    /**
     * get value of given id
     *
     * @param $id
     * @return string
     */
    public function getValue($id)
    {
        if (! $this->records instanceof Tinebase_Record_RecordSet) {
            return '';
        }


        $record = $this->records->filter('id', $id)->getFirstRecord();
        if (! $record) {
            $record = $this->getKeyfieldDefault();
        }

        return $record ? $record->value : '';
    }

    /**
     * get translated value of given id
     *
     * @param $id
     * @return string
     */
    public function getTranslatedValue($id, ?Zend_Locale $locale = null)
    {
        $value = $this->getValue($id);
        if ($value) {
            $translate = Tinebase_Translation::getTranslation($this->_appName, $locale);
            return $translate->_($value);
        }

        return '';
    }

    /**
     * get keyfield record id for given translated value
     *
     * @TODO try all locales
     *
     * @param $translatedValue
     * @return string|null
     */
    public function getIdByTranslatedValue($translatedValue)
    {
        $translate = Tinebase_Translation::getTranslation($this->_appName);
        $originRecord = null;

        foreach ($this->records as $record) {
            // check id & translated value
            if (in_array($translatedValue, array($translate->_($record->value), $record->getId()))) {
                $originRecord = $record;
                break;
            }
        }

        return $originRecord ? $originRecord->getId() : null;
    }
}
