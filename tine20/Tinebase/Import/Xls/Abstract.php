<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;

/**
 * Xls Importer
 *
 * Support for XML definition:
 *  - source
 *  - destination
 *  - excelDate true|false|null If set, it converts an excel timestamp to a DateTime::ATOM string
 *
 * @package     Tinebase
 * @subpackage  Import
 */
abstract class Tinebase_Import_Xls_Abstract extends Tinebase_Import_Abstract
{

    /**
     * Additional options
     *
     *  - sheet: define which sheet is supposed to be imported, the first sheet is 0
     *
     * @var array
     */
    protected $_additionalOptions = [
        'sheet' => 0,
        'startRow' => 2,
        'endRow' => null,
        'startColumn' => 'A',
        'endColumn' => null,
        'headlineRow' => null,
        'mapping' => [],
        'keepImportFile' => null,
        'importFile' => null,
    ];

    /**
     * @var Spreadsheet
     */
    protected $_spreadsheet;

    /**
     * @var Worksheet
     */
    protected $_worksheet;

    /**
     * Set controller, wasn't brave enough to do it in the abstract :(
     *
     * Offertory_Import_OffertoryPlanXlsImport constructor.
     * @param array $_options
     */
    public function __construct(array $_options = [])
    {
        parent::__construct($_options);
        $this->_setController();
    }

    /**
     * @param RowIterator $_resource
     * @return array|boolean
     */
    protected function _getRawData(&$_resource)
    {
        static $recursion = 0;
        if (false === $_resource->valid() || $recursion > 50) {
            $recursion = 0;
            return false;
        }

        $row = $_resource->current();

        $proceed = false;
        foreach ($row->getCellIterator() as $cell) {
            /* @var $cell Cell */
            if (!empty($cell->getValue())) {
                $proceed = true;
            }
        }

        if ($proceed === false) {
            $_resource->next();
            ++$recursion;
            return $this->_getRawData($_resource);
        }
        $recursion = 0;

        $rowArray = $this->_rowToArray($row);
        $_resource->next();

        return $rowArray;
    }

    /**
     * Converts a row to a simple array
     *
     * @param Row $row
     * @return array
     */
    protected function _rowToArray(Row $row)
    {
        $rowArray = [];

        foreach ($row->getCellIterator($this->_options['startColumn'], $this->_options['endColumn']) as $cell) {
            /* @var $cell Cell */
            $rowArray[] = $cell->getValue();
        }

        return $rowArray;
    }

    /**
     * @param string $_filename
     * @param array $_clientRecordData
     * @return array
     * @throws Tinebase_Exception_NotFound
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function importFile($_filename, $_clientRecordData = [])
    {
        if (!file_exists($_filename)) {
            throw new Tinebase_Exception_NotFound("File $_filename not found.");
        }
        
        if ($this->_options['keepImportFile']) {
            $tmpFile = fopen($_filename, 'r');
            $this->_options['importFile'] = Tinebase_TempFile::getInstance()->createTempFileFromStream($tmpFile, 'Import-' . Tinebase_DateTime::now() . '.xlsx');
            fclose($tmpFile);
        }

        // we use the reader and switch to readonly to avoid massive performance-losses
        // see https://stackoverflow.com/questions/16742647/phpexcel-taking-an-extremely-long-time-to-read-excel-file
        // TODO allow to switch to Xls Reader via option / import definition?
        $objReader = IOFactory::createReader('Xlsx');
        $objReader->setReadDataOnly(true);
        $this->_spreadsheet = $objReader->load($_filename);

        $this->_worksheet = $this->_spreadsheet->getSheet($this->_options['sheet']);
        $iterator = $this->_worksheet->getRowIterator($this->_options['startRow'], $this->_options['endRow']);

        return $this->import($iterator, $_clientRecordData);
    }

    /**
     * @param  $_resource RowIterator
     * @param array $_clientRecordData
     * @return array
     */
    public function import($_resource = null, $_clientRecordData = [])
    {
        if (!($_resource instanceof RowIterator)) {
            throw new InvalidArgumentException('Expected RowIterator as $_resource.');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Starting import of ' . ((!empty($this->_options['model'])) ? $this->_options['model'] . 's' : ' records'));
        }

        $this->_initImportResult();
        $this->_beforeImport($_resource);
        $this->_doImport($_resource, $_clientRecordData);
        $this->_logImportResult();
        $this->_afterImport();

        return $this->_importResult;
    }
    
    /**
     * Import from given data
     *
     * @param string $_data
     * @param array $_clientRecordData
     * @return array|void
     * @throws Tinebase_Exception_NotImplemented
     */
    public function importData($_data, $_clientRecordData = [])
    {
        
        throw new Tinebase_Exception_NotImplemented('importData is not yet implemented.');
    }
    
    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->_mapping;
    }

    /**
     * do something with the imported record
     *
     * @param Tinebase_Record_Interface $importedRecord
     */
    protected function _inspectAfterImport($importedRecord)
    {
        $this->keepImportFile($importedRecord);
    }

    /**
     * Save the Import file to the filemanager and create a relation to the imported Record
     * 
     * @param $importedRecord
     * @throws Filemanager_Exception
     * @throws Filemanager_Exception_Quarantined
     * @throws Tinebase_Exception_NotFound
     */
    public function keepImportFile($importedRecord) 
    {
        if ($this->_options['keepImportFile'] && $this->_options['importFile']) {
            $tempFile = $this->_options['importFile'];
            $nodeController = Filemanager_Controller_Node::getInstance();
            $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders';

            try {
                $nodeController->createNodes('/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/Import', 'folder', $_tempFileIds = array());
            } catch (Filemanager_Exception_NodeExists $e){
                // This is fine
            };

            try {
                $nodeController->createNodes('/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/Import/' . $tempFile->name, 'file', $_tempFileIds = array($tempFile->id));
            } catch (Filemanager_Exception_NodeExists $e){
                // This is fine
            };
           
            $importFile = $nodeController->getFileNode(Tinebase_Model_Tree_Node_Path::createFromPath($prefix. '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/Import/' . $tempFile->name));

            if  ($importFile) {
                $relationData = array(
                    array(
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' =>  Filemanager_Model_Node::class,
                        'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                        'related_id' => $importFile->id,
                        'type' => 'IMPORTFILE'
                    )
                );
                Tinebase_Relations::getInstance()->setRelations(Tinebase_Model_MunicipalityKey::class, Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND, $importedRecord->id, $relationData);
            }
        }
    }
}
