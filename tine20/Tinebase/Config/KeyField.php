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
        if (isset($_options['appName'])) {
            $record->setAppName($_options['appName']);
        }
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

        return $this;
    }

    /**
     * set appName
     *
     * @param  string $_appName
     * @return Tinebase_Config_KeyField $this
     */
    public function setAppName($_appName) {
        $this->_appName = $_appName;

        return $this;
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

    /**
     * get value of given id
     *
     * @param $id
     * @return string
     */
    public function getValue($id)
    {
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
    public function getTranslatedValue($id)
    {
        $value = $this->getValue($id);
        if ($value) {
            $translate = Tinebase_Translation::getTranslation($this->_appName);
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