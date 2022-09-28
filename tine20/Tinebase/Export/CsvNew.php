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
    protected $_charset = 'utf-8';
    protected $_doCsvInjectionEscaping = true;

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
        Tinebase_Model_Filter_FilterGroup $_filter = null,
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
        if (null !== $this->_config->charset) {
            $this->_charset = $this->_config->charset;
        }
        if ($this->_config->raw) {
            $this->_doCsvInjectionEscaping = false;
        }

        $this->_fields = [];
        foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
            if (is_string($column->identifier)) {
                $this->_fields[] = $column->identifier;
            }
        }

        if ($this->_hasTwig()) {
            $this->_dumpRecords = false;
        }
    }

    public function generateToStream($stream)
    {
        $this->generate($stream);
    }

    /**
     * generate export
     *
     * @param  resource|null $_fileHandle
     * @throws Tinebase_Exception_Backend
     */
    public function generate($_fileHandle = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Generating new csv export of ' . $this->_modelName);

        if (is_resource($_fileHandle)) {
            $this->_filehandle = $_fileHandle;
        } elseif (!($this->_filehandle = fopen('php://memory', 'w+'))) {
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
     * @throws Tinebase_Exception_NotImplemented
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
        $this->_currentRow[] = $this->_doCsvInjectionEscaping ? $this->csvInjectionEscaping($_value) : $_value;
    }

    protected function _startRow()
    {
        $this->_currentRow = [];
    }

    protected function _endRow()
    {
        if (false === self::fputcsv($this->_filehandle, $this->_currentRow, $this->_delimiter, $this->_enclosure,
                $this->_escape_char, $this->_charset)) {
            throw new Tinebase_Exception_Backend('could not write current row to csv stream');
        }
    }

    /**
     * The php build in fputcsv function is buggy, so we need an own one :-(
     *
     * @param resource $filePointer
     * @param array $dataArray
     * @param char $delimiter
     * @param char $enclosure
     * @param char $escapeEnclosure
     */
    public static function fputcsv($filePointer, $dataArray, $delimiter = ',', $enclosure = '"', $escapeEnclosure = '"', $charset = 'utf-8')
    {
        $string = "";
        $writeDelimiter = false;
        foreach($dataArray as $dataElement) {
            if ($charset != 'utf8') {
                $dataElement = iconv('utf-8', $charset, $dataElement);
            }
            if ($writeDelimiter) {
                $string .= $delimiter;
            }
            if ($enclosure) {
                $escapedDataElement = (!is_array($dataElement)) ? preg_replace("/$enclosure/", $escapeEnclosure . $enclosure, $dataElement) : '';
                $string .= $enclosure . $escapedDataElement . $enclosure;
            } else {
                // e.g. text/tab-separated-values (https://www.iana.org/assignments/media-types/text/tab-separated-values) have no enclosure
                $string .= $dataElement;
            }
            $writeDelimiter = true;
        }
        $string .= "\n";

        fwrite($filePointer, $string);
    }

    /**
     * output result
     * @param resource $_outputStream
     * @throws Tinebase_Exception_Backend
     */
    public function write($_outputStream = null)
    {
        if (false === rewind($this->_filehandle)) {
            throw new Tinebase_Exception_Backend('could not rewind csv stream');
        }
        if (null == $_outputStream) {
            fpassthru($this->_filehandle);
        } else {
            if (false === stream_copy_to_stream($this->_filehandle, $_outputStream)) {
                throw new Tinebase_Exception_Backend('could not copy csv stream to stdout');
            }
        }
    }

    public function save(string $target): void
    {
        if (!($fh = fopen($target, 'w'))) {
            throw new Tinebase_Exception_Backend('could not open target ' . $target);
        }
        try {
            $this->write($fh);
        } finally {
            fclose($fh);
        }
    }

    public function csvInjectionEscaping($_value)
    {
        if (strlen((string)$_value) > 0) {
            switch (ord((string)$_value)) {
                case 9:  // tab vertical
                case 13: // carriage return
                case 43: // +
                case 45: // -
                case 61: // =
                case 64: // @
                    $_value = '\'' . $_value;
            }
        }

        return $_value;
    }
}
