<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * DemoData Import configured by a set_file.yml
 *
 * yml file could look like this:
 *
 * ---
 * name: users groups roles
 *
 * files:
 *   - Admin/User/admin_user_import_csv/*
 *   - Admin/Group/admin_group_import_csv/*
 *   - Admin/Role/admin_role_import_csv/role.csv
 *
 * sets:
 *  - another_set.yml
 *
 * -------------
 *
 * @package     Tinebase
 * @subpackage  Setup
 *
 * @todo allow sets to be anywhere (absolute path)
 * @todo allow to omit setFile param
 */
class Tinebase_Setup_DemoData_ImportSet
{
    protected $_application = null;
    protected $_options = [];

    public function __construct($appName, $options = [])
    {
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName($appName);
        $this->_options['demoData'] = true;
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function importDemodata()
    {
        if (!isset($this->_options['files']) || empty($this->_options['files'])) {
            throw new Tinebase_Exception_InvalidArgument('no yml files given.');
        }

        $importDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR
            . $this->_application->name . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData'
            . DIRECTORY_SEPARATOR;

        foreach ($this->_options['files'] as $filename) {
            if (empty($filename)) {
                throw new Tinebase_Exception_InvalidArgument('filename empty.');
            }

            $path = $importDir . $filename;
            if (file_exists($path)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Importing DemoData set from file ' . $path);
                $setData = yaml_parse_file($path);

                if ($setData) {
                    $this->_importDemoDataSet($setData);
                }
            }
        }
    }

    /**
     * @param array $setData
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _importDemoDataSet($setData)
    {
        if (isset($setData['sets'])) {
            foreach ($setData['sets'] as $set) {
                list($app, $yml) = explode('/', $set);
                $importer = new Tinebase_Setup_DemoData_ImportSet($app, [
                    'files' => [$yml]]);
                $importer->importDemodata();
            }
        }
        
        if (isset($setData['files'])) {
            foreach ($setData['files'] as $file) {
                // @todo handle missing parts
                list($app, $model, $definition, $file) = explode('/', $file);
                $modelName = $app . '_Model_' . $model;
                $importer = new Tinebase_Setup_DemoData_Import($modelName, [
                    'definition' => $definition,
                    'file' => $file,
                ]);
                try {
                    $importer->importDemodata();
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // model has no import files
                }
            }
        } else {
            throw new Tinebase_Exception_InvalidArgument('need at least "files" or "sets" in yaml file');
        }
    }
}
