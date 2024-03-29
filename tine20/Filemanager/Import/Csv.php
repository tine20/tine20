<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Filemanager
 *
 * @package     Filemanager
 * @subpackage  Import
 */
class Filemanager_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array();

    protected $_name = '';

    protected $_folder = '';

    protected $_type = '';

    protected $_user = '';

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $_data = parent::_doConversions($_data);
        $this->_name = $_data['name'];
        $this->_folder = $_data['folder'];
        $this->_type = $_data['type'];
        $this->_user = $_data['container'];
        $this->_displayname = $_data['displayname'];
        return $_data;
    }

    /**
     * do import: loop data -> convert to records -> import records
     *
     * @param mixed $_resource
     * @param array $_clientRecordDatas
     */
    protected function _doImport($_resource = NULL, $_clientRecordDatas = array())
    {
        $clientRecordDatas = $this->_sortClientRecordsByIndex($_clientRecordDatas);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Client record data: ' . print_r($clientRecordDatas, TRUE));

        $recordIndex = 0;
        while (($recordData = $this->_getRawData($_resource)) !== FALSE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Importing record ' . $recordIndex . ' ...');
            $recordToImport = null;
            try {
                // client record overwrites record in import data (only if set)
                $clientRecordData = isset($clientRecordDatas[$recordIndex]['recordData']) ? $clientRecordDatas[$recordIndex]['recordData'] : NULL;
                if ($clientRecordData && Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                    Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Client record: ' . print_r($clientRecordData, TRUE));
                }

                // NOTE _processRawData might return multiple recordDatas
                // NOTE $clientRecordData is always one record
                $recordDataToImport = $clientRecordData ? array($clientRecordData) : $this->_processRawData($recordData);
                $resolveStrategy = $clientRecordData ? $clientRecordDatas[$recordIndex]['resolveStrategy'] : NULL;

                foreach ($recordDataToImport as $idx => $processedRecordData) {
                    $structure = explode('/', $this->_folder); 

                    $this->_user == 'shared' ? $_filenames = 'shared' : $_filenames = $this->_getPersonalPath($this->_user);

                    for ($i = 0; $i < count($structure); $i++) {
                        $_filenames .= '/' . $structure[$i];
                        
                        Filemanager_Controller_Node::getInstance()->createNodes($_filenames, 'folder', $_tempFileIds = array(), $_forceOverwrite = true);
                    }
                    
                    if ($this->_type == 'file') {
                        $tempFile =  $this->_getTempFile($this->_name);
                        if ($tempFile) {
                            $filename = $this->_displayname ? $this->_displayname : $this->_name;
                            Filemanager_Controller_Node::getInstance()->createNodes($_filenames . '/' . $filename, $this->_type, $_tempFileIds = array($tempFile->getId()), $_forceOverwrite = true);
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Could not find demo file: ' . $this->_name);
                            }
                        }
                    }
                    
                    $this->_importResult['totalcount']++;
                }
            } catch (Exception $e) {
                $this->_handleImportException($e, $recordIndex, $recordToImport);
            }
            $recordIndex++;
        }
    }

    /**
     * return persona folder
     *
     * @param string|Tinebase_Model_User $persona
     * @param string $appName
     * @return string
     */
    protected function _getPersonalPath($persona, $appName = 'Filemanager')
    {

        try {
            $user = Tinebase_FullUser::getInstance()->getUserByLoginName($persona);
        }catch (Exception $e)
        {
            return 'shared';
        }

        return Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                $appName,
                Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
            ) . '/' . $user->getId();
    }

    /**
     * Create a Tempfile from an example file
     * 
     * @param $filename
     * @return Tinebase_Model_TempFile|null
     * @throws Tinebase_Exception_Backend_Database
     */
    protected function _getTempFile($filename)
    {
        $tempFileBackend = new Tinebase_TempFile();

        $path = dirname(__FILE__) . '/../Setup/DemoData/files/'. $filename;
        if (file_exists($path)) {
            $handle = fopen($path, 'r');
            $tempfile = $tempFileBackend->createTempFileFromStream($handle, $filename, '');
            fclose($handle);
            
            return $tempfile;  
        } else {
            return $this->_getFileFromUrl($filename);
        }
    }
    
    protected function _getFileFromUrl($filename)
    {
        $tempFileBackend = new Tinebase_TempFile();
        $url = 'https://api.tine20.net/demodata/filemanager/' . $filename;
        $file = fopen($url,'r');
        if ($file) {
            $tempfile = $tempFileBackend->createTempFileFromStream($file, $filename, '');
            return $tempfile;
        } else {
            return null;
        }
    }
}