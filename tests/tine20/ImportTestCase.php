<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * abstract Test class for import tests
 * 
 * @package     Tests
 */
abstract class ImportTestCase extends TestCase
{
    /**
     * importer instance
     * 
     * @var Object
     */
    protected $_instance = null;

    /**
     * @var string $_filename of the export
     */
    protected $_filename = null;

    /**
     * @var boolean
     */
    protected $_deleteImportFile = true;

    protected $_importerClassName = 'Tinebase_Import_Csv_Generic';
    protected $_modelName = null;

    /**
     * @var Tinebase_Model_Container
     */
    protected $_testContainer = null;

    /**
     * tear down tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->_testContainer) {
            try {
                Tinebase_Container::getInstance()->deleteContainer($this->_testContainer, true);
                Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . 'container', 'is_deleted = 1');
            } catch (Tinebase_Exception_NotFound $tenf) {}
        }
        
        // cleanup
        if (file_exists($this->_filename) && $this->_deleteImportFile) {
             unlink($this->_filename);
        }
    }

    /**
     * import helper
     *
     * @param array $_options
     * @param string|Tinebase_Model_ImportExportDefinition $_definition
     * @param Tinebase_Model_Filter_FilterGroup $_exportFilter
     * @param array $clientRecordData
     * @param array $replacements should contain $replacements['from'] and $replacements['to']
     * @throws Tinebase_Exception_NotFound
     * @return array
     */
    protected function _doImport(array $_options = array(), $_definition = null, Tinebase_Model_Filter_FilterGroup $_exportFilter = null, $clientRecordData = [], $replacements = null)
    {
        if ((! $this->_importerClassName || ! $this->_modelName) && ! $_definition) {
            throw new Tinebase_Exception_NotFound('No import class or model name given');
        }

        if ($_definition === null) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getGenericImport($this->_modelName);
        } else {
            $definition = ($_definition instanceof Tinebase_Model_ImportExportDefinition) ? $_definition : Tinebase_ImportExportDefinition::getInstance()->getByName($_definition);
            if ($definition->plugin) {
                $this->_importerClassName = $definition->plugin;
            }
        }
        $this->_instance = call_user_func_array($this->_importerClassName . '::createFromDefinition' , array($definition, $_options));

        // export first
        if ($_exportFilter !== NULL) {
            $exporter = Tinebase_Export::factory($_exportFilter, 'csv', Tinebase_Core::getApplicationInstance($this->_modelName));
            $this->_filename = $exporter->generate();

            if ($replacements) {
                $csv = file_get_contents($this->_filename);
                $csv = str_replace($replacements['from'], $replacements['to'], $csv);
                file_put_contents($this->_filename, $csv);
            }
        }

        // then import
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        try {
            $result = $this->_instance->importFile($this->_filename, $clientRecordData);
        } finally {
            Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(false);
        }

        return $result;
    }
}
