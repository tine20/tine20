<?php
/**
 * Tinebase Csv Export class based on new abstract class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Csv Export class  based on new abstract class
 *
 * @package     Tinebase
 * @subpackage    Export
 */
class Tinebase_Export_CsvNew extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface
{
    /**
     * format strings
     *
     * @var string
     */
    protected $_format = 'newCsv';

    /**
     * csv filehandle resource
     *
     * @var resource
     */
    protected $_filehandle = null;

    protected $_currentRow;

    protected $_delimiter = ';';
    protected $_enclosure = '"';
    protected $_escape_char = '\\';

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function __construct(
        Tinebase_Model_Filter_FilterGroup $_filter,
        Tinebase_Controller_Record_Interface $_controller = null,
        $_additionalOptions = array()
    ) {
        parent::__construct($_filter, $_controller, $_additionalOptions);

        if (null !== $this->_config->delimiter) {
            $this->_delimiter = $this->_config->delimiter;
        }
        if (null !== $this->_config->enclosure) {
            $this->_enclosure = $this->_config->enclosure;
        }
        if (null !== $this->_config->escape_char) {
            $this->_escape_char = $this->_config->escape_char;
        }

        $this->_fields = [];
        foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
            if (is_string($column->identifier)) {
                $this->_fields[] = $column->identifier;
            }
        }
    }
    /**
     * generate export
     *
     * @return string|boolean filename
     */
    public function generate()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Generating new csv export of ' . $this->_modelName);

        if (!($this->_filehandle = fopen('php://memory', 'w'))) {
            throw new Tinebase_Exception_Backend('could not create export memory stream');
        }

        $this->_exportRecords();
    }

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/csv';
    }

    /**
     * @return string
     *
    public static function getDefaultFormat()
    {
        return 'csv';
    }*/

    /**
     * @param string $_key
     * @param string $_value
     */
    protected function _setValue($_key, $_value)
    {
        throw new Tinebase_Exception_NotImplemented('no templates for csv');
    }

    /**
     * @param string $_value
     */
    protected function _writeValue($_value)
    {
        $this->_currentRow[] = $_value;
    }

    protected function _startRow()
    {
        $this->_currentRow = [];
    }

    protected function _endRow()
    {
        if (false === fputcsv($this->_filehandle, $this->_currentRow, $this->_delimiter, $this->_enclosure,
                $this->_escape_char)) {
            throw new Tinebase_Exception_Backend('could not write current row to csv stream');
        }
    }

    /**
     * output result
     */
    public function write($_outputStream = STDOUT)
    {
        if (false === rewind($this->_filehandle)) {
            throw new Tinebase_Exception_Backend('could not rewind csv stream');
        }
        if (false === stream_copy_to_stream($this->_filehandle, $_outputStream)) {
            throw new Tinebase_Exception_Backend('could not copy csv stream to stdout');
        }
        fclose($this->_filehandle);
    }
}
