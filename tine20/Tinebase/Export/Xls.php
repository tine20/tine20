<?php
/**
 * Tinebase Xls/Xlsx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

/**
 * Tinebase Xls/Xlsx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 */

class Tinebase_Export_Xls extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface, Tinebase_Export_Convertible {
    use Tinebase_Export_Convertible_PreviewServicePdf;
    
    /**
     * the document
     *
     * @var PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    protected $_spreadsheet;

    /**
     * format strings
     *
     * @var string
     */
    protected $_format = 'xls';

    protected $_rowOffset = 0;

    protected $_rowCount = 0;

    protected $_columnCount = 1;

    protected $_cloneRow;

    protected $_cloneRowStyles = array();

    protected $_cloneGroupEndRowStyles = null;

    protected $_cloneGroupEndRow = null;

    protected $_excelVersion;


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
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter = null, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        parent::__construct($_filter, $_controller, $_additionalOptions);

        if (empty($this->_config->writer)) {
            $this->_excelVersion = 'Xlsx';
        } else {
            $this->_excelVersion = $this->_config->writer;
        }
    }

    public static function getDefaultFormat()
    {
        return 'xlsx';
    }

    /**
     * get excel object
     *
     * @return PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function getDocument()
    {
        return $this->_spreadsheet;
    }

    /**
     * get export content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        $contentType = ('Xlsx' === $this->_excelVersion)
            // Excel 2007 content type
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            // Excel 5 content type or other
            : 'application/vnd.ms-excel';

        return $contentType;
    }

    /**
     * return download filename
     * @param string $_appName
     * @param string $_format
     * @return string
     */
    public function getDownloadFilename($_appName, $_format)
    {
        $result = parent::getDownloadFilename($_appName, $_format);

        if ('Xlsx' === $this->_excelVersion && $_format !== 'xlsx') {
            // excel2007 extension is .xlsx
            $result .= 'x';
        }

        return $result;
    }

    /**
     * get export format string (csv, ...)
     *
     * @return string
     * @throws Tinebase_Exception_NotFound
     */
    public function getFormat()
    {
        if ('xls' === $this->_format && 'Xlsx' === $this->_excelVersion) {
            // excel2007 extension is .xlsx
            return 'xlsx';
        }

        if ($this->_format === null) {
            throw new Tinebase_Exception_NotFound('Format string not found.');
        }
        
        return $this->_format;
    }

    /**
     * output result
     *
     * @param string $_target
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function write($_target = 'php://output')
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating and sending xls to client (Format: ' . $this->_excelVersion . ').');
        $xlswriter = IOFactory::createWriter($this->_spreadsheet, $this->_excelVersion);

        // precalculating formula values costs tons of time, because sum formulas are like SUM C1:C65000
        /** @noinspection PhpUndefinedMethodInspection */
        $xlswriter->setPreCalculateFormulas(FALSE);

        $xlswriter->save($_target);
    }

    /**
     * @param string $_target
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function save($_target)
    {
        $this->write($_target);    
    }

    /**
     * generate export
     * @throws Tinebase_Exception
     */
    public function generate()
    {
        $this->_rowCount = 0;
        $this->_columnCount = 1;
        $this->_createDocument();
        $this->_exportRecords();
        $this->_replaceTine20ImagePaths();
    }

    protected function _startRow()
    {
        $this->_rowCount += 1;
        $this->_columnCount = 1;

        //insert cloned row
        if ($this->_rowOffset > 0) {
            $newRowOffset = $this->_rowOffset + $this->_rowCount - 1;
            $sheet = $this->_spreadsheet->getActiveSheet();

            if ($this->_rowCount > 1) {
                $sheet->insertNewRowBefore($newRowOffset);
            }

            foreach($this->_cloneRow as $newRow) {
                $cell = $sheet->getCell($newRow['column'] . $newRowOffset);
                $cell->setValue(preg_replace('/\$\{twig[^}]+\}/', '$0#' . $this->_rowCount, $newRow['value']));
                $cell->setXfIndex($newRow['XFIndex']);
            }

            $rowDimension = $sheet->getRowDimension($newRowOffset);
            foreach($this->_cloneRowStyles as $func => $value) {
                call_user_func(array($rowDimension, $func), $value);
            }
        }
    }

    protected function _endGroup()
    {
        if (null === $this->_cloneGroupEndRow) {
            return;
        }

        $this->_rowCount += 1;
        //insert cloned row
        if ($this->_rowOffset > 0) {
            $newRowOffset = $this->_rowOffset + $this->_rowCount - 1;
            $sheet = $this->_spreadsheet->getActiveSheet();

            if ($this->_rowCount > 1) {
                $sheet->insertNewRowBefore($newRowOffset);
            }

            foreach($this->_cloneGroupEndRow as $newRow) {
                $cell = $sheet->getCell($newRow['column'] . $newRowOffset);
                $cell->setValue(preg_replace('/\$\{twig[^}]+\}/', '$0#' . $this->_rowCount, $newRow['value']));
                $cell->setXfIndex($newRow['XFIndex']);
            }

            $rowDimension = $sheet->getRowDimension($newRowOffset);
            foreach($this->_cloneGroupEndRowStyles as $func => $value) {
                call_user_func(array($rowDimension, $func), $value);
            }
        }

        $this->_renderTwigTemplate();
    }

    protected function _createDocument()
    {
        Tinebase_Export_Spreadsheet_NumberFormat::fillBuiltInFormatCodes();

        $templateFile = $this->_getTemplateFilename();

        if ($templateFile !== NULL) {
            // autodetection works much better with file ending, thanks to phpspreadsheet we can simply use the reader version! (at least until ms will change the file endings) :-)
            $tmpFile = Tinebase_TempFile::getTempPath() . '.' . strtolower($this->_excelVersion);
            if (false === copy($templateFile, $tmpFile)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not copy template file to temp path');
                throw new Tinebase_Exception('could not copy template file to temp path');
            }

            if (! $this->_config->reader || 'autodetection' === $this->_config->reader) {
                $this->_spreadsheet = IOFactory::load($tmpFile);
            } else {
                $reader = IOFactory::createReader($this->_config->reader);
                $this->_spreadsheet = $reader->load($tmpFile);
            }
            
            $activeSheet = isset($this->_config->sheet) ? $this->_config->sheet : 0;
            $this->_spreadsheet->setActiveSheetIndex($activeSheet);

            $this->_hasTemplate = true;
            $this->_dumpRecords = true;
            $this->_writeGenericHeader = true;
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new PhpSpreadsheet object.');
            $this->_spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        }
    }

    /**
     * TODO performance?
     * TODO performance: cache cells with replacement tokens, update cache when cloning a row
     * TODO not just replace first, replace all!
     *
     * @param string $_key
     * @param string $_value
     */
    protected function _setValue($_key, $_value)
    {
        if (true !== $this->_iterationDone) {
            $_key = $_key . '#' . $this->_rowCount;
        }

        if (null !== ($cell = $this->_findCell($_key))) {
            $cell->setValue(str_replace($_key, $_value, $cell->getValue()));
        }

        foreach($this->_spreadsheet->getActiveSheet()->getDrawingCollection() as $drawing) {
            $desc = $drawing->getDescription();
            if (\strpos($desc, $_key) !== false) {
                $drawing->setDescription(str_replace($_key, $_value, $desc));
            }
        }
    }

    /**
     * @param string $_search
     * @return Cell
     */
    protected function _findCell($_search)
    {
        $sheet = $this->_spreadsheet->getActiveSheet();

        $rowIter = $sheet->getRowIterator();

        /** @var Row $row */
        foreach($rowIter as $row) {
            $cellIter = $row->getCellIterator();
            try {
                $cellIter->setIterateOnlyExistingCells(true);
            } catch (\PhpOffice\PhpSpreadsheet\Exception $pe) {
                continue;
            }
            /** @var PhpOffice\PhpSpreadsheet\Cell $cell */
            foreach($cellIter as $cell) {
                if (false !== strpos($cell->getValue(), $_search)) {
                    return $cell;
                }
            }
        }

        return null;
    }

    /**
     * TODO pass value type? for dates etc.?
     *
     * @param string $_value
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _writeValue($_value)
    {
        $sheet = $this->_spreadsheet->getActiveSheet();

        $cell = $sheet->getCellByColumnAndRow($this->_columnCount++, $this->_rowCount);

        $cell->setValue($_value);
    }

    /**
     * TODO build up cache of replacement tokens so that _setValue can be implemented faster!
     *
     * @return string
     */
    public function _getTwigSource()
    {
        $i = 0;
        $source = '[';

        $sheet = $this->_spreadsheet->getActiveSheet();

        $rowIter = $sheet->getRowIterator();
        /** @var Row $row */
        foreach($rowIter as $row) {
            $cellIter = $row->getCellIterator();
            try {
                $cellIter->setIterateOnlyExistingCells(true);
            } catch (\PhpOffice\PhpSpreadsheet\Exception $pe) {
                continue;
            }
            /** @var Cell $cell */
            foreach($cellIter as $cell) {
                if (false !== strpos($cell->getValue(), '${twig:') &&
                        preg_match_all('/\${twig:([^}]+?)}/s', $cell->getValue(), $matches, PREG_SET_ORDER)) {
                    foreach($matches as $match) {
                        $this->_twigMapping[$i] = $match[0];
                        $source .= ($i === 0 ? '' : ',') . '{{' . $match[1] . '}}';
                        ++$i;
                    }
                }
            }
        }

        foreach($this->_spreadsheet->getActiveSheet()->getDrawingCollection() as $drawing) {
            $desc = $drawing->getDescription();
            if (false !== strpos($desc, '${twig:') &&
                preg_match_all('/\${twig:([^}]+?)}/s', $desc, $matches, PREG_SET_ORDER)
            ) {
                foreach ($matches as $match) {
                    $this->_twigMapping[$i] = $match[0];
                    $source .= ($i === 0 ? '' : ',') . '{{' . $match[1] . '}}';
                    ++$i;
                }
            }
        }

        $source .= ']';

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' returning twig template source: ' . $source);

        return $source;
    }

    protected function _findFirstFreeRow()
    {
        $sheet = $this->_spreadsheet->getActiveSheet();

        $rowIter = $sheet->getRowIterator();
        /** @var Row $row */
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach($rowIter as $row) {
            ++$this->_rowCount;
        }
    }

    protected function _onBeforeExportRecords()
    {
        // TODO header row?

        if (null === ($block = $this->_findCell('${ROW}'))) {
            $this->_findFirstFreeRow();
            return;
        }
        $startColumn = $block->getColumn();
        $this->_rowOffset = $block->getRow();

        if (null === ($block = $this->_findCell('${/ROW}'))) {
            $this->_findFirstFreeRow();
            return;
        }

        $this->_dumpRecords = false;
        $this->_writeGenericHeader = false;

        $endColumn = $block->getColumn();
        if ($block->getRow() !== $this->_rowOffset) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ . ' block tags need to be in the same row');
            throw new Tinebase_Exception_UnexpectedValue('block tags need to be in the same row');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' found block...');

        $sheet = $this->_spreadsheet->getActiveSheet();

        /** @var  $rowIterator */
        $rowIterator = $sheet->getRowIterator($this->_rowOffset);
        $row = $rowIterator->current();
        $rowDimension = $sheet->getRowDimension($row->getRowIndex());
        $this->_cloneRowStyles = array(
            'setCollapsed'      => $rowDimension->getCollapsed(),
            'setOutlineLevel'   => $rowDimension->getOutlineLevel(),
            'setRowHeight'      => $rowDimension->getRowHeight(),
            'setVisible'        => $rowDimension->getVisible(),
            'setXfIndex'        => $rowDimension->getXfIndex(),
            'setZeroHeight'     => $rowDimension->getZeroHeight()
        );
        $cellIterator = $row->getCellIterator($startColumn, $endColumn);

        $replace = array('${ROW}', '${/ROW}');
        /** @var Cell $cell */
        foreach($cellIterator as $cell) {
            $this->_cloneRow[] = array(
                'column'        => $cell->getColumn(),
                'value'         => str_replace($replace, '', $cell->getValue()),
                'XFIndex'       => $cell->getXfIndex()
            );
            $cell->setValue(null);
            $cell->setXfIndex(0);
        }

        if ($this->_config->group) {
            $this->_findGroupEnd();
        }
    }

    protected function _findGroupEnd()
    {
        if (null === ($block = $this->_findCell('${GROUP_END}'))) {
            return;
        }
        $startColumn = $block->getColumn();
        $rowOffset = $block->getRow();

        if (null === ($block = $this->_findCell('${/GROUP_END}'))) {
            return;
        }

        $endColumn = $block->getColumn();
        if ($block->getRow() !== $rowOffset) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ . ' block tags need to be in the same row');
            throw new Tinebase_Exception_UnexpectedValue('block tags need to be in the same row');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' found group end...');

        $sheet = $this->_spreadsheet->getActiveSheet();

        /** @var  $rowIterator */
        $rowIterator = $sheet->getRowIterator($rowOffset);
        $row = $rowIterator->current();
        $rowDimension = $sheet->getRowDimension($row->getRowIndex());
        $this->_cloneGroupEndRowStyles = array(
            'setCollapsed'      => $rowDimension->getCollapsed(),
            'setOutlineLevel'   => $rowDimension->getOutlineLevel(),
            'setRowHeight'      => $rowDimension->getRowHeight(),
            'setVisible'        => $rowDimension->getVisible(),
            'setXfIndex'        => $rowDimension->getXfIndex(),
            'setZeroHeight'     => $rowDimension->getZeroHeight()
        );
        $cellIterator = $row->getCellIterator($startColumn, $endColumn);

        $replace = array('${GROUP_END}', '${/GROUP_END}');
        /** @var Cell $cell */
        foreach($cellIterator as $cell) {
            $this->_cloneGroupEndRow[] = array(
                'column'        => $cell->getColumn(),
                'value'         => str_replace($replace, '', $cell->getValue()),
                'XFIndex'       => $cell->getXfIndex()
            );
            $cell->setValue(null);
            $cell->setXfIndex(0);
        }
    }

    /**
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _replaceTine20ImagePaths()
    {
        /** @var Drawing $drawing */
        foreach($this->_spreadsheet->getActiveSheet()->getDrawingCollection() as $drawing) {
            $desc = $drawing->getDescription();
            if (strpos($desc, '://') !== false) {
                $desc = trim($desc);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' found url: ' . $desc);

                if (!in_array(mb_strtolower(pathinfo($desc, PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'png', 'gif'))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' unsupported file extension: ' . $desc);
                    continue;
                }

                $fileContent = file_get_contents($desc);
                if (!empty($fileContent)) {

                    $tempFile = Tinebase_TempFile::getTempPath();
                    if (strlen($fileContent) !== file_put_contents($tempFile, $fileContent)) {
                        throw new Tinebase_Exception('could not store filecontents in a temp file');
                    }

                    $this->_replaceDrawing($tempFile, $drawing);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                        Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ . ' could not get file content: ' . $desc);

                    throw new Tinebase_Exception_UnexpectedValue('could not get file content: ' . $desc);
                }
            }
        }
    }

    protected function _replaceDrawing($filePath, Drawing $drawing)
    {
        list($newWidth, $newHeight) = getimagesize($filePath);
        $oldWidth = $drawing->getWidth();
        $oldHeight = $drawing->getHeight();

        $drawing->setResizeProportional(false);
        if ($newWidth <= $oldWidth && $newHeight <= $oldHeight) {
            $drawing->setWidth($newWidth);
            $drawing->setHeight($newHeight);
        } else {
            // 0,25         1           4
            // 4            4           1
            $oldRatio = $oldWidth / $oldHeight;
            // 0,75         3           4
            $newRatio = $newWidth / $newHeight;

            if ($newRatio >= $oldRatio) {
                $drawing->setWidth($oldWidth);
                $drawing->setHeight((int)($newHeight * $oldWidth / $newWidth));
            } else {
                $drawing->setHeight($oldHeight);
                $drawing->setWidth((int)($newWidth * $oldHeight / $newHeight));
            }
        }

        $drawing->setPath($filePath);
    }


    /**
     * @param $to
     * @param $from
     * @return null|string
     * @throws Tinebase_Exception_NotFound
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    function convert($to, $from = null)
    {
        if (!$from) {
            $from = Tinebase_TempFile::getTempPath();
            $this->save($from);
        }
        
        switch($to) {
            case Tinebase_Export_Convertible::PDF:
                return $this->convertToPdf($from);
            default:
                return null;
        }
    }
}