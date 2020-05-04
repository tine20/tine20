<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Abstract class for DemoData Import
 *
 * @package     Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_DemoData_Import
{
    const IMPORT_DIR = 'importDir';

    protected $_application = null;
    protected $_options = [];

    /**
     * Tinebase_Setup_DemoData_Import constructor.
     * @param string $modelName
     * @param array $options
     */
    public function __construct($modelName, $options = [])
    {
        $extract = Tinebase_Application::extractAppAndModel($modelName);
        $this->_options['modelName'] = $extract['modelName'];
        $this->_options['dryrun'] = false;
        $this->_options['demoData'] = true;
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName($extract['appName']);
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * @throws Tinebase_Exception_NotFound
     */
    public function importDemodata()
    {
        $importDir = isset($this->_options[self::IMPORT_DIR]) ?  $this->_options[self::IMPORT_DIR] :
            dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR
            . $this->_application->name . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData'
            . DIRECTORY_SEPARATOR . 'import'. DIRECTORY_SEPARATOR . $this->_options['modelName'];

        if (! file_exists($importDir)) {
            throw new Tinebase_Exception_NotFound('Import dir not found: ' . $importDir);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Importing files in import dir ' . $importDir );

        // TODO allow more filters / subdirs
        $fh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($importDir), RecursiveIteratorIterator::CHILD_FIRST);
        $importedDemoDataFiles = 0;
        foreach ($fh as $splFileInfo) {
            if (isset($this->_options['file']) && $this->_options['file'] !== '*' && $splFileInfo->getFilename() !== $this->_options['file']) {
                // skip
                continue;
            }
            $result = $this->_importDemoDataFile($splFileInfo);
            if ($result) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Import result: ' . print_r($result['results']->toArray(), true));
                $importedDemoDataFiles++;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Imported ' . $importedDemoDataFiles . ' demo data files');

    }

    /**
     * @param SplFileInfo $splFileInfo
     * @return null
     */
    protected function _importDemoDataFile(SplFileInfo $splFileInfo)
    {
        // TODO allow xls
        $importFileExtensions = ['csv'];

        if (in_array($splFileInfo->getExtension(), $importFileExtensions)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Importing file ' . $splFileInfo->getPathname());
            $filename = $splFileInfo->getFilename();

            // create importer
            if (isset($this->_options['definition'])) {
                $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($this->_options['definition']);
            } else {
                // create generic import definition if not found in options
                $definition = Tinebase_ImportExportDefinition::getInstance()->getGenericImport($this->_options['modelName']);
            }
            $importClass = $definition->plugin;
            if(empty($importClass)) {
                $importClass = $this->_application->name . '_Import_Csv';
            }
            if (!class_exists($importClass)){
                $importClass = Tinebase_Import_Csv_Generic::class;
            }
            $this->_importer = call_user_func_array([$importClass, 'createFromDefinition'], [$definition, $this->_options]);

            $result = $this->_importer->importFile($splFileInfo->getPath() . DIRECTORY_SEPARATOR . $filename);
            return $result;
        }

        return null;
    }
}
