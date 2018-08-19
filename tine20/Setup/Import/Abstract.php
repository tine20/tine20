<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract class to import data from various sources
 *
 * @package     Setup
 * @subpackage  Import
 */
abstract class Setup_Import_Abstract
{
    /**
     * Name of the importer class to search for when walking the apps
     * {APP}_Setup_Import_{_classPostFix}
     *
     * overwrite this in concrete class
     *
     * @var string
     */
    protected $_classPostFix = NULL;

    /**
     * List of apps that need to be imported before this importer may run
     *
     * ALL LOWERCASE
     *
     * overwrite this in concrete class
     *
     * @var array
     */
    protected $_requiredApps = array();

    /**
     * The record / model class to use to create the records to be imported
     *
     * overwrite this in concrete class
     *
     * @var string
     */
    protected $_recordClassToImport = NULL;

    /**
     * The controller to use to import the records
     *
     * set this in concrete class, in the constructor for example
     *
     * @var Tinebase_Controller_Record_Abstract
     */
    protected $_controllerToImport = NULL;

    /**
     * The getData delegator that returns the data to import
     *
     * set this in concrete class, in the constructor for example
     * or overwrite the _getData function
     *
     * @var mixed
     */
    protected $_getDataDelegator = NULL;

    /**
     * @var Zend_Config
     */
    protected $_config = NULL;

    /**
     * constructs an importer
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config, $getDataDeletegator = NULL)
    {
        $this->_config = $config;
        $this->_getDataDelegator = $getDataDeletegator;
    }

    public function getRequiredApps()
    {
        return $this->_requiredApps;
    }

    public function import()
    {
        $data = $this->_getData();

        /*$importedData = */$this->_importData($data);
    }

    protected function _getData()
    {
        return $this->_getDataDelegator->getData();
    }

    protected function _importData($_data)
    {
        foreach($_data as $rawRecord) {
            try {

                $this->_amendRawData($rawRecord);

                $record = $this->_createRecordToImport($rawRecord);

                $this->_amendRecordData($record);

                $importedRecord = $this->_importRecord($record);

                $this->_inspectCreatedRecord($importedRecord);

            } catch (Setup_Exception_Import_SkipDataset $seikd) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' skipped dataset: ' . $seikd->getMessage());
            }
        }
    }

    protected function _amendRawData(&$_data) {}

    protected function _createRecordToImport(array $_rawRecord)
    {
        return new $this->_recordClassToImport($_rawRecord);
    }

    protected function _amendRecordData($_record) {}

    protected function _importRecord(Tinebase_Record_Interface $_record)
    {
        if ($_record->getId()) {
            return $this->_controllerToImport->update($_record, false);
        } else {
            return $this->_controllerToImport->create($_record, false);
        }
    }

    protected function _inspectCreatedRecord($_record) {}

    public function importAll()
    {
        // walk apps
        $apps = Setup_Controller::getInstance()->searchApplications();
        $importedApps = array();
        $failedApps = array();
        $importerToRun = array();

        foreach($apps['results'] as $app) {
            if (   $app['install_status'] == 'uptodate'
                && ($this->_config->allApps
                    || ($this->_config->{strtolower($app['name'])}
                        && $this->_config->{strtolower($app['name'])}->enabled))) {

                $class_name = $app['name'] . '_Setup_Import_' . $this->_classPostFix;
                if (! class_exists($class_name)) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " no import for {$app['name']} available");
                    continue;
                }

                try {
                    /**
                     * @var Setup_Import_Abstract $importer
                     */
                    $importer = new $class_name($this->_config);
                    if (0 === count(array_diff($importer->getRequiredApps(), $importedApps))) {

                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' about to import app ' . $app['name']);

                        $importer->import();
                        $importedApps[strtolower($app['name'])] = strtolower($app['name']);

                    } else {
                        $importerToRun[strtolower($app['name'])] = $importer;
                    }
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " import for {$app['name']} failed " . $e);
                }
            }
        }

        // cycle the importer until all of them met their requirements and could be executed
        // fail if we get stuck there
        $didSomething = true;
        while (count($importerToRun) > 0 && true === $didSomething) {
            $didSomething = false;
            foreach($importerToRun as $app => $importer) {
                /**
                 * @var Setup_Import_Abstract $importer
                 */

                // check if requirements are met
                if (0 === count(array_diff($importer->getRequiredApps(), $importedApps))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' about to import app ' . $app);
                    try {
                        $importer->import();
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " import for $app failed ". $e);
                        // we didnt do anything, so dont set variables, but still we want to be removed and not executed again
                        $failedApps[$app] = $app;
                        continue;
                    }
                    $importedApps[$app] = $app;
                    $didSomething = true;
                }
            }

            $importerToRun = array_diff_key($importerToRun, $importedApps, $failedApps);
        }

        if (count($importerToRun) > 0) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " import for " . join(', ', array_keys($importerToRun)) . " could not be run due to unmet requirements");
        }
    }
}