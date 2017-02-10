<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * generic csv import class
 * 
 * @package     Tinebase
 * @subpackage  Import
 */
class Tinebase_Import_Csv_Generic extends Tinebase_Import_Csv_Abstract
{
    /**
     * creates a new importer from an importexport definition
     *
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Calendar_Import_Ical
     *
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new Tinebase_Import_Csv_Generic(self::getOptionsArrayFromDefinition($_definition, $_options));
    }

    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Options: ' . print_r($_options, true));

        parent::__construct($_options);

        $this->_setFieldMapping();
    }

    /**
     * set import field mapping
     *
     * TODO remove code duplication with \Tinebase_Export_Csv::_getFields
     * TODO use ModelConfig
     */
    protected function _setFieldMapping()
    {
        $modelName = $this->_options['model'];
        $record = new $modelName(array(), TRUE);
        $extract = Tinebase_Application::extractAppAndModel($modelName);
        $appName = $extract['appName'];
        $customfields = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($appName, $modelName)->name;

        $fields = array();
        foreach ($record->getFields() as $key) {
            if ($key === 'customfields') {
                foreach ($customfields as $cfName) {
                    $fields[] = array('source' => $cfName, 'destination' => $cfName);
                }
            } else {
                $fields[] = array('source' => $key, 'destination' => $key);
//                if (in_array($key, array_keys($this->_specialFields))) {
//                    $fields[] = $this->_specialFields[$key];
//                }
            }
        }

        if ($record->has('tags')) {
            $fields[] = array('source' => 'tags', 'destination' => 'tags');
        }

//        $fields = array_diff($fields, $this->_skipFields);
//        $fields = array_merge($fields, $this->_getRelationFields());

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fields to import: ' . print_r($fields, true));

        $this->_options['mapping']['field'] = $fields;
    }
}
