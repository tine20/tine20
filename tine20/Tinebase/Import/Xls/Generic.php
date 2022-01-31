<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * @package     Tinebase
 * @subpackage  Import
 */
class Tinebase_Import_Xls_Generic extends Tinebase_Import_Xls_Abstract
{
    /**
     * Used to store mapping information
     *
     * @var array
     */
    protected $_mapping = [];

    /**
     * @var array
     */
    protected $_indexMapping = [];

    /**
     * @var bool
     */
    protected $_disableMappingAutoresolving = false;

    /**
     * @var bool
     */
    protected $_retrieveContainerFromData = false;

    /**
     * Tinebase_Import_Xls_Generic constructor.
     * @param array $_options
     */
    public function __construct(array $_options = [])
    {
        parent::__construct($_options);

        if (isset($_options['retrieveContainerFromData']) && $retrieveContainerFromData = $_options['retrieveContainerFromData']) {
            $this->_retrieveContainerFromData = filter_var($retrieveContainerFromData, FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($_options['disableMappingAutoresolving']) && $disableMappingAutoresolving = $_options['disableMappingAutoresolving']) {
            $this->_disableMappingAutoresolving = filter_var($disableMappingAutoresolving, FILTER_VALIDATE_BOOLEAN);
        }
    }


    /**
     * @param  $_resource
     * @throws \InvalidArgumentException
     */
    protected function _beforeImport($_resource = null)
    {
        if (null === $this->_options['headlineRow']) {
            return;
        }

        $rowIterator = $this->_worksheet->getRowIterator($this->_options['headlineRow'],
            $this->_options['headlineRow']);

        foreach ($rowIterator->current()->getCellIterator($this->_options['startColumn'],
            $this->_options['endColumn']) as $cell) {

            /* @var $cell \PhpOffice\PhpSpreadsheet\Cell */
            $this->_indexMapping[] = $cell->getValue();
        }

        if ($this->_disableMappingAutoresolving === false) {
            $this->_autoResolveLocalisedMapping();
        } else {
            $this->_mapping = $this->_indexMapping;
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function _autoResolveLocalisedMapping()
    {
        /* @var $modelConfig Tinebase_ModelConfiguration */
        $modelConfig = $this->_options['model']::getConfiguration();

        if (!$modelConfig) {
            throw new InvalidArgumentException(__CLASS__ . ' can only import models with a valid Tinebase_ModelConfiguration.');
        }

        $fields = $modelConfig->getFields();
        $messages = array_merge(Tinebase_Translation::getTranslation('Tinebase')->getMessages(),
            Tinebase_Translation::getTranslation($modelConfig->getAppName())->getMessages());

        foreach ($this->_indexMapping as $index => $mapping) {
            if ($mapping === null) {
                continue;
            }

            $englishMessage = array_keys(array_filter($messages, function ($v) use ($mapping) {
                return $v === $mapping || (is_array($v) && in_array($mapping, $v, true));
            }, ARRAY_FILTER_USE_BOTH));

            $englishString = array_shift($englishMessage);

            // In case string couldn't be found in translations
            if ($englishString === null) {
                continue;
            }

            foreach ($fields as $field) {
                if (isset($field['label']) && $field['label'] === $englishString) {
                    $this->_indexMapping[$index] = $englishString;
                    $this->_mapping[$englishString] = $field['fieldName'];
                    continue 2;
                    break;
                }
            }
        }
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param null $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_NotFound
     */
    protected function _importRecord($_record, $_resolveStrategy = null, $_recordData = array())
    {
        // can resolve the container the record is supposed to be imported into, therefore a file can container multiple records for multiple containers
        if ($this->_retrieveContainerFromData && $containerName = $_record->{$_record::getConfiguration()->getContainerProperty()}) {
            $container = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter([
                'name' => $containerName,
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId()
            ]))->getFirstRecord();

            if ($container) {
                $_record->container_id = $container->getId();
            }
        }

        return parent::_importRecord($_record, $_resolveStrategy, $_recordData);
    }


    /**
     * @param array $_data
     * @return array
     * @throws Exception
     */
    protected function _doMappingConversion($_data)
    {
        $_data = parent::_doMappingConversion($_data);

        foreach ($this->_options['mapping']['field'] as $index => $field) {
            if (!(isset($field['destination']) || array_key_exists('destination',
                        $field)) || $field['destination'] == '' || !isset($_data[$field['destination']])) {
                continue;
            }

            $key = $field['destination'];

            // Convert excel dates to datetime objects
            if (isset($field['excelDate']) && filter_var($field['excelDate'], FILTER_VALIDATE_BOOLEAN) === true) {
                $_data[$key] = Date::excelToDateTimeObject($_data[$key],
                    Tinebase_Core::getUserTimezone())->format(DateTime::ATOM);
            }
        }

        return $_data;
    }

    /**
     * do the mapping and replacements
     *
     * @param array $_data
     * @return array
     * @throws Exception
     */
    protected function _doMapping($_data)
    {
        $mappedData = [];

        $fields = array_column($this->_options['mapping']['field'], 'destination');
        
        foreach ($_data as $index => $data) {
            $key = null;

            // Automatically resolved mapping
            if ($this->_disableMappingAutoresolving === false && array_key_exists($this->_indexMapping[$index],
                    $this->_mapping)) {
                $key = $this->_mapping[$this->_indexMapping[$index]];
            }
            
            // Traditional mapping with configuration of source and destination
            foreach ($this->_options['mapping']['field'] as $field) {
                if (!isset($field['destination'], $field['source']) || $field['source'] !== $this->_indexMapping[$index]) {
                    continue;
                }

                $key = $field['destination'];
            }

            if (!$key || !in_array($key, $fields, true)) {
                continue;
            }

            $mappedData[$key] = $data;
        }

        return $mappedData;
    }

    protected function _getMappedFieldBy($name)
    {
        foreach ($this->_options['mapping']['field'] as $field) {
            if ($field['source'] === $name) {
                return $field['destination'];
            }
        }

        return $name;
    }
}
