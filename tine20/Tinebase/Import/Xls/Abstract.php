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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;

/**
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
        'headlineRow' => null
    ];

    /**
     * @var Spreadsheet
     */
    protected $_spreadsheet = null;

    /**
     * @var Worksheet
     */
    protected $_worksheet = null;

    /**
     * Set controller, wasn't brave enough to do it in the abstract :(
     *
     * Offertory_Import_OffertoryPlanXlsImport constructor.
     * @param array $_options
     */
    public function __construct(array $_options = array())
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
        if (false === $_resource->valid()) {
            return false;
        }

        $row = $this->_rowToArray($_resource->current());
        $_resource->next();

        return $row;
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

    public function importFile($_filename, $_clientRecordData = [])
    {
        if (!file_exists($_filename)) {
            throw new Tinebase_Exception_NotFound("File $_filename not found.");
        }

        $this->_spreadsheet = IOFactory::load($_filename);
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
     */
    public function importData($_data, $_clientRecordData = [])
    {
        throw new Tinebase_Exception_NotImplemented('importData is not yet implemented.');
    }
}
