<?php
/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 */

class Tinebase_Export_Doc extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface
{

    /**
     * the document
     *
     * @var \PhpOffice\PhpWord\PhpWord
     */
    protected $_docObject;

    /**
     * the template to work on
     *
     * @var Tinebase_Export_Richtext_TemplateProcessor
     */
    protected $_docTemplate = null;

    /**
     * format strings
     *
     * @var string
     */
    protected $_format = 'docx';

    /**
     * @var int
     */
    protected $_rowCount = 0;

    /**
     * @var array
     */
    protected $_templateVariables = null;

    /**
     * @var array
     */
    protected $_dataSources = array();

    /**
     * @var boolean
     */
    protected $_skip = false;

    /**
     * @var Tinebase_Export_Richtext_TemplateProcessor
     */
    protected $_currentProcessor = null;



    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'application/vnd.ms-word';
    }


    /**
     * return download filename
     *
     * @param string $_appName
     * @param string $_format
     * @return string
     */
    public function getDownloadFilename($_appName, $_format)
    {
        return 'letter_' . strtolower($_appName) . '.docx';
    }

    public static function getDefaultFormat()
    {
        return 'docx';
    }

    /**
     * generate export
     */
    public function generate()
    {
        $this->_rowCount = 0;
        $this->_writeGenericHeader = false;
        $this->_dumpRecords = false;
        $this->_createDocument();
        $this->_exportRecords();
        if (null !== $this->_docTemplate) {
            $this->_docTemplate->replaceTine20ImagePaths();
        }
    }

    /**
     * output result
     */
    public function write()
    {
        $document = $this->getDocument();
        $tempfile = $document->save();
        readfile($tempfile);
        unlink($tempfile);
    }

    public function save($filename)
    {
        $document = $this->getDocument();
        $tempfile = $document->save();

        copy($tempfile, $filename);
        unlink($tempfile);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function _cutXml($str)
    {
        return substr($str, 5);
    }

    /**
     * @param $_name
     */
    protected function _startDataSource($_name)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' starting datasource ' . $_name);

        if (!isset($this->_dataSources[$_name])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' datasource not found, skipping data!');
            $this->_skip = true;
            return;
        }

        $this->_currentProcessor = $this->_dataSources[$_name];
        $this->_rowCount = 0;
    }

    /**
     * @param $_name
     */
    protected function _endDataSource($_name)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ending datasource ' . $_name);

        $data = '';

        if (false === $this->_skip) {

            $this->_unwrapProcessors();

            /** @var Tinebase_Export_Richtext_TemplateProcessor $processor */
            $processor = $this->_dataSources[$_name];
            $data = $this->_cutXml($processor->getMainPart());
        }

        $this->_lastGroupValue = null;
        $this->_skip = false;
        $this->_currentProcessor = $this->_docTemplate;
        $this->_docTemplate->setValue('DATASOURCE_' . $_name, $data);
    }

    protected function _unwrapProcessors()
    {
        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $this->_currentProcessor = $this->_currentProcessor->getParent();
            $this->_currentProcessor->setValue('${RECORD_BLOCK}', '');
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_STANDARD) {
            $this->_currentProcessor->setValue('${RECORD_ROW}', '');
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP) {
            $processor = $this->_currentProcessor->getParent();
            $processor->setValue('${GROUP_BLOCK}', $this->_cutXml($this->_currentProcessor->getMainPart()));
            $this->_currentProcessor = $processor;
        }
    }

    protected function _startGroup()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' starting group...');

        if (true === $this->_skip) {
            return;
        }

        if ($this->_currentProcessor->hasConfig('group')) {
            $this->_currentProcessor = $this->_currentProcessor->getConfig('group');
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP) {
            if ($this->_rowCount > 0 && $this->_currentProcessor->hasConfig('groupSeparator')) {
                $this->_currentProcessor->append($this->_currentProcessor->getConfig('groupSeparator'));
            }

            if ($this->_currentProcessor->hasConfig('groupHeader')) {
                $this->_currentProcessor->append($this->_currentProcessor->getConfig('groupHeader'));
            }

            $this->_currentProcessor->append($this->_currentProcessor->getConfig('groupXml'));
        } elseif ($this->_currentProcessor->hasConfig('recordRow')) {
            $recordRow = $this->_currentProcessor->getConfig('recordRow');

            if ($this->_rowCount > 0 && isset($recordRow['groupSeparatorRow'])) {
                $this->_currentProcessor->setValue('${RECORD_ROW}', $recordRow['groupSeparatorRow'] . '${RECORD_ROW}');
            }

            if (isset($recordRow['groupHeaderRow'])) {
                $this->_currentProcessor->setValue('${RECORD_ROW}', $recordRow['groupHeaderRow'] . '${RECORD_ROW}');
            }
        }
    }

    protected function _endGroup()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ending group...');

        if (true === $this->_skip) {
            return;
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP) {
            $this->_currentProcessor->setValue('${RECORD_ROW}', '');
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $this->_currentProcessor = $this->_currentProcessor->getParent();
            if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP) {
                $this->_currentProcessor->setValue('${RECORD_BLOCK}', '');
            }
        }

        if ($this->_currentProcessor->hasConfig('recordRow')) {
            $recordRow = $this->_currentProcessor->getConfig('recordRow');
            if (isset($recordRow['groupFooterRow'])) {
                $this->_currentProcessor->setValue('${RECORD_ROW}', $recordRow['groupFooterRow'] . '${RECORD_ROW}');
            }
        }

        if ($this->_currentProcessor->hasConfig('groupFooter')) {
            $this->_currentProcessor->append($this->_currentProcessor->getConfig('groupFooter'));
        }
    }

    protected function _startRow()
    {
        if (true === $this->_skip) {
            return;
        }

        $this->_rowCount += 1;

        if ($this->_currentProcessor->hasConfig('recordRow')) {
            $recordRow = $this->_currentProcessor->getConfig('recordRow');
            $this->_currentProcessor->setValue('${RECORD_ROW}', $recordRow['recordRow'] . '${RECORD_ROW}');
        } else {
            if ($this->_currentProcessor->hasConfig('record')) {
                $this->_currentProcessor = $this->_currentProcessor->getConfig('record');
            }

            if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                $data = '';
                if ($this->_rowCount > 1 && $this->_currentProcessor->hasConfig('separator')) {
                    $data .= $this->_currentProcessor->getConfig('separator');
                }

                if ($this->_currentProcessor->hasConfig('header')) {
                    $data .= $this->_currentProcessor->getConfig('header');
                }

                $data .= $this->_currentProcessor->getConfig('recordXml') . '${RECORD_BLOCK}';
                $this->_currentProcessor->getParent()->setValue('${RECORD_BLOCK}', $data);
            } else {
                throw new Tinebase_Exception_UnexpectedValue('template and definition do not match');
            }
        }
    }

    protected function _endRow()
    {
        if (true === $this->_skip) {
            return;
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD &&
                $this->_currentProcessor->hasConfig('footer')) {
            $this->_currentProcessor->getParent()->setValue('${RECORD_BLOCK}',
                $this->_currentProcessor->getConfig('footer') . '${RECORD_BLOCK}');
        }
    }

    /**
     * get word object
     *
     * @return \PhpOffice\PhpWord\PhpWord | \PhpOffice\PhpWord\TemplateProcessor
     */
    public function getDocument()
    {
        return $this->_docTemplate ? $this->_docTemplate : $this->_docObject;
    }


    /**
     * create new PhpWord document
     *
     * @return void
     */
    protected function _createDocument()
    {
        \PhpOffice\PhpWord\Settings::setTempDir(Tinebase_Core::getTempDir());

        $templateFile = $this->_getTemplateFilename();
        $this->_docObject = new \PhpOffice\PhpWord\PhpWord();

        if ($templateFile !== null) {
            $this->_hasTemplate = true;
            $this->_docTemplate = new Tinebase_Export_Richtext_TemplateProcessor($templateFile);
        }
    }

    /**
     * @param string $_key
     * @param string $_value
     */
    protected function _setValue($_key, $_value)
    {
        if (true === $this->_skip) {
            return;
        }

        $this->_currentProcessor->setValue($_key, $_value);

        if($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $this->_currentProcessor->getParent()->setValue($_key, $_value);
        }
    }

    /**
     * @param string $_value
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _writeValue($_value)
    {
        throw new Tinebase_Exception_NotImplemented(__CLASS__ . ' can not provide a meaningful default '
                . 'implementation. Subclass needs to provide or avoid it from being called');
    }

    public function _getTwigSource()
    {
        $i = 0;
        $source = '[';
        foreach ($this->_getTemplateVariables() as $placeholder) {
            if (strpos($placeholder, 'twig:') === 0) {
                $this->_twigMapping[$i] = $placeholder;
                $source .= ($i === 0 ? '' : ',') . '{{' . html_entity_decode(substr($placeholder, 5), ENT_QUOTES | ENT_XML1) . '}}';
                ++$i;
            }
        }
        $source .= ']';

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' returning twig template source: ' . $source);

        return $source;
    }

    protected function _onBeforeExportRecords()
    {
        if (null !== $this->_docTemplate) {
            $this->_findAndReplaceDatasources();

            if (empty($this->_dataSources)) {
                $this->_findAndReplaceGroup($this->_docTemplate);
                $this->_currentProcessor = $this->_docTemplate;

            }
        }
    }

    protected function _findAndReplaceDatasources()
    {
        foreach ($this->_getTemplateVariables() as $placeholder) {
            if (strpos($placeholder, 'DATASOURCE') === 0 && preg_match('/DATASOURCE_(.*)/', $placeholder, $match)) {
                if (null === ($dataSource = $this->_docTemplate->cloneBlock($placeholder, 1, false))) {
                    throw new Tinebase_Exception_UnexpectedValue('clone block for ' . $placeholder . ' failed');
                }

                $dataSource = '<?xml' . $dataSource;
                $processor = new Tinebase_Export_Richtext_TemplateProcessor($dataSource, true,
                    Tinebase_Export_Richtext_TemplateProcessor::TYPE_DATASOURCE);
                $this->_dataSources[$match[1]] = $processor;
                $this->_docTemplate->replaceBlock($match[0], '${' . $match[0] . '}');

                $this->_findAndReplaceGroup($processor);
            }
        }
    }

    /**
     * @param Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor
     */
    protected function _findAndReplaceGroup(Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor)
    {
        $config = array();

        if (null !== ($group = $_templateProcessor->cloneBlock('GROUP_BLOCK', 1, false))) {
            $_templateProcessor->replaceBlock('GROUP_BLOCK', '${GROUP_BLOCK}');
            $groupProcessor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml' . $group, true,
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP, $_templateProcessor);

            if (null === ($recordProcessor = $this->_findAndReplaceRecord($groupProcessor))) {
                $config['recordRow'] = $this->_findAndReplaceRecordRow($groupProcessor);
            } else {
                $config['record'] = $recordProcessor;
            }
            if (null !== ($groupHeader = $_templateProcessor->cloneBlock('GROUP_HEADER', 1, false))) {
                $_templateProcessor->replaceBlock('GROUP_HEADER', '');
                $config['groupHeader'] = $groupHeader;
            }
            if (null !== ($groupFooter = $_templateProcessor->cloneBlock('GROUP_FOOTER', 1, false))) {
                $_templateProcessor->replaceBlock('GROUP_FOOTER', '');
                $config['groupFooter'] = $groupFooter;
            }
            if (null !== ($groupSeparator = $_templateProcessor->cloneBlock('GROUP_SEPARATOR', 1, false))) {
                $_templateProcessor->replaceBlock('GROUP_SEPARATOR', '');
                $config['groupSeparator'] = $groupSeparator;
            }
            if (isset($config['recordRow']) && (isset($config['groupHeaderRow']) || isset($config['groupFooterRow']) ||
                    isset($config['groupSeparatorRow']))) {
                throw new Tinebase_Exception_UnexpectedValue('GROUP must not contain header, footer or separator rows');
            }
            $config['groupXml'] = $this->_cutXml($groupProcessor->getMainPart());
            $groupProcessor->setMainPart('<?xml');
            $groupProcessor->setConfig($config);
            $config = array('group' => $groupProcessor);

        } elseif (null === ($record = $this->_findAndReplaceRecord($_templateProcessor))) {
            $config['recordRow'] = $this->_findAndReplaceRecordRow($_templateProcessor);
        } else {
            $config['record'] = $record;
        }

        $_templateProcessor->setConfig($config);
    }

    /**
     * @param Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor
     * @return Tinebase_Export_Richtext_TemplateProcessor|null
     */
    protected function _findAndReplaceRecord(Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor)
    {
        if (null !== ($recordBlock = $_templateProcessor->cloneBlock('RECORD_BLOCK', 1, false))) {
            $_templateProcessor->replaceBlock('RECORD_BLOCK', '${RECORD_BLOCK}');
            $processor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml', true,
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD, $_templateProcessor);
            $config = array(
                'recordXml'     => $recordBlock
            );

            if (null !== ($recordHeader = $_templateProcessor->cloneBlock('RECORD_HEADER', 1, false))) {
                $_templateProcessor->replaceBlock('RECORD_HEADER', '');
                $config['header'] = $recordHeader;
            }

            if (null !== ($recordFooter = $_templateProcessor->cloneBlock('RECORD_FOOTER', 1, false))) {
                $_templateProcessor->replaceBlock('RECORD_FOOTER', '');
                $config['footer'] = $recordFooter;
            }

            if (null !== ($recordSeparator = $_templateProcessor->cloneBlock('RECORD_SEPARATOR', 1, false))) {
                $_templateProcessor->replaceBlock('RECORD_SEPARATOR', '');
                $config['separator'] = $recordSeparator;
            }
            $processor->setConfig($config);
            return $processor;
        }

        return null;
    }

    /**
     * @param Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    protected function _findAndReplaceRecordRow(Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor)
    {
        $result = array();

        if (preg_match('/<w:tbl.*?(\$\{twig:[^}]*record[^}]*})/is', $_templateProcessor->getMainPart(), $matches)) {
            $result['recordRow'] = $_templateProcessor->replaceRow($matches[1], '${RECORD_ROW}');

            if (strpos($_templateProcessor->getMainPart(), '${GROUP_HEADER}') !== false) {
                $result['groupHeaderRow'] = str_replace('${GROUP_HEADER}', '', $_templateProcessor->replaceRow('${GROUP_HEADER}', ''));
            }

            if (strpos($_templateProcessor->getMainPart(), '${GROUP_FOOTER}') !== false) {
                $result['groupFooterRow'] = str_replace('${GROUP_FOOTER}', '', $_templateProcessor->replaceRow('${GROUP_FOOTER}', ''));
            }

            if (strpos($_templateProcessor->getMainPart(), '${GROUP_SEPARATOR}') !== false) {
                $result['groupSeparatorRow'] = str_replace('${GROUP_SEPARATOR}', '', $_templateProcessor->replaceRow('${GROUP_SEPARATOR}', ''));
            }
        } else {
            throw new Tinebase_Exception_UnexpectedValue('template without RECORD_BLOCK needs to contain a table row with a replacement variable');
        }

        return $result;
    }


    /**
     * now simulate processIteration and finish with _onAfterExportRecords
     *
     * @param array $_result
     */
    protected function _onAfterExportRecords(array $_result)
    {
        $this->_unwrapProcessors();

        parent::_onAfterExportRecords($_result);
    }

    protected function _getTemplateVariables()
    {
        if (null === $this->_templateVariables) {
            $this->_templateVariables = $this->_docTemplate->getVariables();
        }

        return $this->_templateVariables;
    }
}
