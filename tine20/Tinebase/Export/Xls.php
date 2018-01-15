<?php
/**
 * Tinebase Xls/Xlsx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Xls/Xlsx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 */

class Tinebase_Export_Xls extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface {

    /**
     * the document
     *
     * @var PHPExcel
     */
    protected $_excelObject;

    /**
     * format strings
     *
     * @var string
     */
    protected $_format = 'xls';

    protected $_rowOffset = 0;

    protected $_rowCount = 0;

    protected $_columnCount = 0;

    protected $_cloneRow = null;

    protected $_cloneRowStyles = array();

    protected $_excelVersion = null;


    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        parent::__construct($_filter, $_controller, $_additionalOptions);

        if (empty($this->_config->writer)) {
            $this->_excelVersion = 'Excel2007';
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
     * @return PHPExcel
     */
    public function getDocument()
    {
        return $this->_excelObject;
    }

    /**
     * get export content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        $contentType = ('Excel2007' === $this->_excelVersion)
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

        if ('Excel2007' === $this->_excelVersion && $_format !== 'xlsx') {
            // excel2007 extension is .xlsx
            $result .= 'x';
        }

        return $result;
    }

    /**
     * output result
     *
     * @param string $_target
     */
    public function write($_target = 'php://output')
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating and sending xls to client (Format: ' . $this->_excelVersion . ').');
        $xlswriter = PHPExcel_IOFactory::createWriter($this->_excelObject, $this->_excelVersion);

        // precalculating formula values costs tons of time, because sum formulas are like SUM C1:C65000
        /** @noinspection PhpUndefinedMethodInspection */
        $xlswriter->setPreCalculateFormulas(FALSE);

        $xlswriter->save($_target);
    }

    /**
     * generate export
     */
    public function generate()
    {
        $this->_rowCount = 0;
        $this->_columnCount = 0;
        $this->_createDocument();
        $this->_exportRecords();
        $this->_replaceTine20ImagePaths();
    }

    protected function _startRow()
    {
        $this->_rowCount += 1;
        $this->_columnCount = 0;

        //insert cloned row
        if ($this->_rowOffset > 0) {
            $newRowOffset = $this->_rowOffset + $this->_rowCount - 1;
            $sheet = $this->_excelObject->getActiveSheet();

            // this doesn't work sadly...
            //if ($sheet->getHighestRow() >= $newRowOffset) {
                if ($this->_rowCount > 1) {
                    $sheet->insertNewRowBefore($newRowOffset + 1);
                }
            //}

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

    protected function _createDocument()
    {
        Tinebase_Export_Spreadsheet_NumberFormat::fillBuildInTypes();

        $templateFile = $this->_getTemplateFilename();

        if ($templateFile !== NULL) {

            $tmpFile = Tinebase_TempFile::getTempPath();
            if (false === copy($templateFile, $tmpFile)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not copy template file to temp path');
                throw new Tinebase_Exception('could not copy template file to temp path');
            }

            if (! $this->_config->reader || 'autodetection' === $this->_config->reader) {
                $this->_excelObject = PHPExcel_IOFactory::load($tmpFile);
            } else {
                $reader = PHPExcel_IOFactory::createReader($this->_config->reader);
                $this->_excelObject = $reader->load($tmpFile);
            }

            // need to unregister the zip stream wrapper because it is overwritten by PHPExcel!
            // TODO file a bugreport to PHPExcel
            @stream_wrapper_restore("zip");

            $activeSheet = isset($this->_config->sheet) ? $this->_config->sheet : 0;
            $this->_excelObject->setActiveSheetIndex($activeSheet);

            $this->_hasTemplate = true;
            $this->_dumpRecords = true;
            $this->_writeGenericHeader = true;
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new PHPExcel object.');
            $this->_excelObject = new PHPExcel();
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

        foreach($this->_excelObject->getActiveSheet()->getDrawingCollection() as $drawing) {
            $desc = $drawing->getDescription();
            if (\strpos($desc, $_key) !== false) {
                $drawing->setDescription(str_replace($_key, $_value, $desc));
            }
        }
    }

    /**
     * @param string $_search
     * @return PHPExcel_Cell
     */
    protected function _findCell($_search)
    {
        $sheet = $this->_excelObject->getActiveSheet();

        $rowIter = $sheet->getRowIterator();
        /** @var PHPExcel_Worksheet_Row $row */
        foreach($rowIter as $row) {
            $cellIter = $row->getCellIterator();
            try {
                $cellIter->setIterateOnlyExistingCells(true);
            } catch (PHPExcel_Exception $pe) {
                continue;
            }
            /** @var PHPExcel_Cell $cell */
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
        $sheet = $this->_excelObject->getActiveSheet();

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

        $sheet = $this->_excelObject->getActiveSheet();

        $rowIter = $sheet->getRowIterator();
        /** @var PHPExcel_Worksheet_Row $row */
        foreach($rowIter as $row) {
            $cellIter = $row->getCellIterator();
            try {
                $cellIter->setIterateOnlyExistingCells(true);
            } catch (PHPExcel_Exception $pe) {
                continue;
            }
            /** @var PHPExcel_Cell $cell */
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

        foreach($this->_excelObject->getActiveSheet()->getDrawingCollection() as $drawing) {
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
        $sheet = $this->_excelObject->getActiveSheet();

        $rowIter = $sheet->getRowIterator();
        /** @var PHPExcel_Worksheet_Row $row */
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

        $sheet = $this->_excelObject->getActiveSheet();

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
        /** @var PHPExcel_Cell $cell */
        foreach($cellIterator as $cell) {
            $this->_cloneRow[] = array(
                'column'        => $cell->getColumn(),
                'value'         => str_replace($replace, '', $cell->getValue()),
                'XFIndex'       => $cell->getXfIndex()
            );
            $cell->setValue();
            $cell->setXfIndex();
            // TODO update replacement cache in case we implement it
        }
    }

    /**
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _replaceTine20ImagePaths()
    {
        /** @var PHPExcel_Worksheet_Drawing $drawing */
        foreach($this->_excelObject->getActiveSheet()->getDrawingCollection() as $drawing) {
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

    protected function _replaceDrawing($filePath, PHPExcel_Worksheet_Drawing $drawing)
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
}