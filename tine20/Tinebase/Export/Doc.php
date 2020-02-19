<?php
/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 */

class Tinebase_Export_Doc extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface, Tinebase_Export_Convertible
{
    use Tinebase_Export_Convertible_PreviewServicePdf;

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
     * @var string
     */
    protected $_currentDataSource = null;

    /**
     * @var boolean
     */
    protected $_skip = false;

    /**
     * @var Tinebase_Export_Richtext_TemplateProcessor
     */
    protected $_currentProcessor = null;

    protected $_subTwigTemplates = array();
    protected $_subTwigMappings = array();

    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'application/vnd.ms-word';
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
            $this->_docTemplate->postProcessMarkers();
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

        $this->_firstIteration = true;
        $this->_currentProcessor = $this->_dataSources[$_name];
        $this->_currentDataSource = $_name;
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
        /*if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBRECORD) {
            $parent = $this->_currentProcessor->getParent();
            $name = '${R' . $this->_currentProcessor->getConfig('name') . '}';
            if ($parent->getType()  === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBGROUP) {
                $this->_currentProcessor = $parent;
            } elseif ($parent->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                $parent = $parent->getParent();
            }
            $parent->setValue($name, '');
        }
        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBGROUP) {
            $parent = $this->_currentProcessor->getParent();
            $name = '${R' . $this->_currentProcessor->getConfig('name') . '}';
            if ($parent->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                $parent = $parent->getParent();
            }
            $parent->setValue($name, '');
        }*/

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

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP ||
                $this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBGROUP) {
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

            if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD ||
                    $this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBRECORD) {
                $data = '';
                if ($this->_rowCount > 1 && $this->_currentProcessor->hasConfig('separator')) {
                    $data .= $this->_currentProcessor->getConfig('separator');
                }

                if ($this->_currentProcessor->hasConfig('header')) {
                    $data .= $this->_currentProcessor->getConfig('header');
                }

                if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                    $data .= $this->_currentProcessor->getConfig('recordXml') . '${RECORD_BLOCK}';
                    $this->_currentProcessor->getParent()->setValue('${RECORD_BLOCK}', $data);
                } else {
                    $name = '${R' . $this->_currentProcessor->getConfig('name') . '}';
                    $data .= $this->_currentProcessor->getConfig('recordXml') . $name;
                    if (($parent = $this->_currentProcessor->getParent()) && $parent->getType() ===
                            Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                        $parent = $parent->getParent();
                    }
                    $parent->setValue($name, $data);
                }
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

        if ($this->_currentProcessor->hasConfig('subgroups')) {
            foreach ($this->_currentProcessor->getConfig('subgroups') as $property => $group) {
                $this->_executeSubTemplate($property, $group);
            }
        }
        if ($this->_currentProcessor->hasConfig('subrecords')) {
            foreach ($this->_currentProcessor->getConfig('subrecords') as $property => $record) {
                $this->_executeSubTemplate($property, $record);
            }
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD &&
                $this->_currentProcessor->hasConfig('footer')) {
            $this->_currentProcessor->getParent()->setValue('${RECORD_BLOCK}',
                $this->_currentProcessor->getConfig('footer') . '${RECORD_BLOCK}');
        }
    }

    /**
     * @param string $_name
     * @param Tinebase_Export_Richtext_TemplateProcessor $_processor
     */
    protected function _executeSubTemplate($_name, Tinebase_Export_Richtext_TemplateProcessor $_processor)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' starting sub tempalte: ' . $_name);

        if (strpos($_name, '#') !== false) {
            list($property) = explode('#', $_name);
        } else {
            $property = $_name;
        }

        $disallowedKeys = null;
        if ($property === 'customfields' && $this->_config->customfieldBlackList) {
            $disallowedKeys = Tinebase_Helper_ZendConfig::getChildrenStrings($this->_config->customfieldBlackList,
                'name');
        }

        $recordSet = $this->_currentRecord->{$property};
        if (is_array($recordSet)) {
            if (count($recordSet) === 0) {
                return;
            }
            if (($record = reset($recordSet)) instanceof Tinebase_Record_Interface) {
                if (null !== $disallowedKeys) {
                    $realRecordSet = new Tinebase_Record_RecordSet(get_class($record));
                    foreach($recordSet as $key => $value) {
                        if (in_array($key, $disallowedKeys)) {
                            continue;
                        }
                        $realRecordSet->addRecord($value);
                    }
                    $recordSet = $realRecordSet;
                } else {
                    $recordSet = new Tinebase_Record_RecordSet(get_class($record), $recordSet);
                }
            } else {
                $realRecordSet = new Tinebase_Record_RecordSet(Tinebase_Record_Generic::class, array());
                $mergedRecords = array();
                foreach($recordSet as $recordArray) {
                    if (!is_array($recordArray)) {
                        return;
                    }
                    $mergedRecords = array_merge($recordArray, $mergedRecords);
                }
                $validators = array_fill_keys(array_keys($mergedRecords), array(Zend_Filter_Input::ALLOW_EMPTY => true));
                unset($validators['customfields']);
                foreach($recordSet as $key => $recordArray) {
                    if (null !== $disallowedKeys && in_array($key, $disallowedKeys)) {
                        continue;
                    }
                    $record = new Tinebase_Record_Generic(array(), true);
                    $record->setValidators($validators);
                    $record->setFromArray($recordArray);
                    $realRecordSet->addRecord($record);
                }
                $recordSet = $realRecordSet;
            }
        } elseif (is_object($recordSet)) {
            if ($recordSet instanceof Tinebase_Record_Interface) {
                $recordSet = new Tinebase_Record_RecordSet(get_class($recordSet), array($recordSet));
            } elseif (!$recordSet instanceof Tinebase_Record_RecordSet) {
                return;
            }
        } else {
            return;
        }

        $oldTemplateVariables = $this->_templateVariables;
        $oldProcessor = $this->_currentProcessor;
        $oldDocTemplate = $this->_docTemplate;
        $oldRowCount = $this->_rowCount;
        $subTempName = $this->_currentDataSource . '_' . $_name;
        $oldState = $this->_getCurrentState();
        foreach (array_keys($oldState) as $key) {
            $this->{$key} = null;
        }
        $this->_templateVariables = null;


        $this->_currentProcessor = $_processor;
        $this->_rowCount = 0;
        $this->_docTemplate = $_processor;

        if (!isset($this->_subTwigTemplates[$subTempName])) {
            $this->_twig->addLoader(
                new Tinebase_Twig_CallBackLoader($this->_templateFileName . $subTempName, $this->_getLastModifiedTimeStamp(),
                    array($this, '_getTwigSource')));

            $this->_twigTemplate = $this->_twig->load($this->_templateFileName . $subTempName);
            $this->_subTwigTemplates[$subTempName] = $this->_twigTemplate;
            $this->_subTwigMappings[$subTempName] = $this->_twigMapping;
        } else {
            $this->_twigTemplate = $this->_subTwigTemplates[$subTempName];
            $this->_twigMapping = $this->_subTwigMappings[$subTempName];
        }

        $this->processIteration($recordSet);

        $result = $this->_cutXml($this->_currentProcessor->getMainPart());
        $replacementName = ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBGROUP ?
            'RSUBGROUP_' : 'RSUBRECORD_') . $_name;

        $this->_templateVariables = $oldTemplateVariables;
        $this->_docTemplate = $oldDocTemplate;
        $this->_currentProcessor = $oldProcessor;
        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $this->_currentProcessor->getParent()->setValue($replacementName, $result);
        } else {
            $this->_currentProcessor->setValue($replacementName, $result);
        }
        $this->_rowCount = $oldRowCount;
        $this->_setCurrentState($oldState);
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
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);

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

        $_value = (string)$_value;
        if (strlen($_value) > 0) {
            $_value = str_replace(["\r", Tinebase_Export_Richtext_TemplateProcessor::NEW_LINE_PLACEHOLDER], ['', "\n"],
                $_value);
            $_value = explode("\n", $_value);
            array_walk($_value, function(&$val) {
                $val = htmlspecialchars($val);
            });
            $_value = join('</w:t><w:br/><w:t>', $_value);
        }

        $this->_currentProcessor->setValue($_key, $_value);

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $this->_currentProcessor->getParent()->setValue($_key, $_value);
        } elseif ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBRECORD) {
            $this->_currentProcessor->getParent()->setValue($_key, $_value);
            if ($this->_currentProcessor->getParent()->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                $this->_currentProcessor->getParent()->getParent()->setValue($_key, $_value);
            }
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
        if (null === $this->_currentProcessor) {
            $this->_onBeforeExportRecords();
        }
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
        if (null !== $this->_docTemplate && null === $this->_currentProcessor) {
            $this->_currentProcessor = $this->_docTemplate;
            $this->_findAndReplaceDatasources();

            if (empty($this->_dataSources)) {
                $this->_findAndReplaceGroup($this->_docTemplate);
            }
        }
    }

    protected function _findAndReplaceDatasources()
    {
        foreach ($this->_getTemplateVariables() as $placeholder) {
            if (strpos($placeholder, 'DATASOURCE') === 0 && preg_match('/DATASOURCE_(.*)/', $placeholder, $match)) {
                if (null === ($dataSource = $this->_docTemplate->findBlock($placeholder, '${' . $match[0] . '}'))) {
                    throw new Tinebase_Exception_UnexpectedValue('find&replace block for ' . $placeholder . ' failed');
                }

                $dataSource = '<?xml' . $dataSource;
                $processor = new Tinebase_Export_Richtext_TemplateProcessor($dataSource, true,
                    Tinebase_Export_Richtext_TemplateProcessor::TYPE_DATASOURCE, null, $match[1]);
                $this->_dataSources[$match[1]] = $processor;

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

        if (null !== ($group = $_templateProcessor->findBlock('GROUP_BLOCK', '${GROUP_BLOCK}'))) {
            $groupProcessor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml' . $group, true,
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_GROUP, $_templateProcessor);

            if (null === ($recordProcessor = $this->_findAndReplaceRecord($groupProcessor))) {
                $config['recordRow'] = $this->_findAndReplaceRecordRow($groupProcessor);
            } else {
                $config['record'] = $recordProcessor;
            }
            if (null !== ($groupHeader = $_templateProcessor->findBlock('GROUP_HEADER', ''))) {
                $config['groupHeader'] = $groupHeader;
            }
            if (null !== ($groupFooter = $_templateProcessor->findBlock('GROUP_FOOTER', ''))) {
                $config['groupFooter'] = $groupFooter;
            }
            if (null !== ($groupSeparator = $_templateProcessor->findBlock('GROUP_SEPARATOR', ''))) {
                $config['groupSeparator'] = $groupSeparator;
            }
            if (isset($config['recordRow']) && (isset($config['groupHeader']) || isset($config['groupFooter']) ||
                    isset($config['groupSeparator'])) && (isset($config['recordRow']['groupHeaderRow']) ||
                    isset($config['recordRow']['groupFooterRow']) || isset($config['recordRow']['groupSeparatorRow']))) {
                throw new Tinebase_Exception_UnexpectedValue('GROUP with record row must not contain header, footer or separator as table row and group block at the same time');
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
        if (null !== ($recordBlock = $_templateProcessor->findBlock('RECORD_BLOCK', '${RECORD_BLOCK}'))) {
            $processor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml' . $recordBlock, true,
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD, $_templateProcessor);
            $this->_findAndReplaceSubGroup($processor);
            $config = array(
                'recordXml'     => $this->_cutXml($processor->getMainPart())
            );
            $processor->setMainPart('<?xml');

            if (null !== ($recordHeader = $_templateProcessor->findBlock('RECORD_HEADER', ''))) {
                $config['header'] = $recordHeader;
            }

            if (null !== ($recordFooter = $_templateProcessor->findBlock('RECORD_FOOTER', ''))) {
                $config['footer'] = $recordFooter;
            }

            if (null !== ($recordSeparator = $_templateProcessor->findBlock('RECORD_SEPARATOR', ''))) {
                $config['separator'] = $recordSeparator;
            }
            $processor->setConfig(array_merge($processor->getConfig(), $config));
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

        if (preg_match('/<w:tbl.*?(\$\{twig:[^}]*record[^}]*})/is', $_templateProcessor->getMainPart(), $matches) ||
            preg_match('/<w:tbl.*?(\{\{[^}]*record[^}]*}})/is', $_templateProcessor->getMainPart(), $matches)) {
            $result['recordRow'] = $_templateProcessor->replaceRow($matches[1], '${RECORD_ROW}');
            $processor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml' . $result['recordRow'], true,
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD, $_templateProcessor);
            $this->_findAndReplaceSubGroup($processor);
            $processor->setConfig(array(
                'recordXml'     => $this->_cutXml($processor->getMainPart())
            ));
            $processor->setMainPart('<?xml');
            $result['recordRowProcessor'] = $processor;

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
     * @param Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor
     * @throws Tinebase_Exception
     */
    protected function _findAndReplaceSubGroup(Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor)
    {
        $parentConfig = array('subgroups' => array(), 'subrecords' => array());

        do {
            $foundGroup = null;
            $foundRecord = null;
            foreach ($_templateProcessor->getVariables() as $var) {
                if (strpos($var, 'SUBGROUP') === 0) {
                    $foundGroup = $var;
                    break;
                }
                if (null === $foundRecord && strpos($var, 'SUBRECORD') === 0) {
                    $foundRecord = $var;
                }
            }

            $config = array();
            if (null !== $foundGroup) {
                if (null !== ($group = $_templateProcessor->findBlock($foundGroup, '${R' . $foundGroup . '}'))) {
                    list(,$propertyName) = explode('_', $foundGroup);
                    $groupProcessor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml' . $group, true,
                        Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBGROUP, $_templateProcessor);

                    if (null === ($recordProcessor = $this->_findAndReplaceSubRecord($groupProcessor))) {
                        throw new Tinebase_Exception('subgroup without record block: ' . $foundGroup);
                    } else {
                        $config['record'] = $recordProcessor;
                    }
                    if (null !== ($groupHeader = $_templateProcessor->findBlock('SUBG_HEADER_' . $propertyName, ''))) {
                        $config['groupHeader'] = $groupHeader;
                    }
                    if (null !== ($groupFooter = $_templateProcessor->findBlock('SUBG_FOOTER_' . $propertyName, ''))) {
                        $config['groupFooter'] = $groupFooter;
                    }
                    if (null !== ($groupSeparator = $_templateProcessor->findBlock('SUBG_SEPARATOR_' . $propertyName, ''))) {
                        $config['groupSeparator'] = $groupSeparator;
                    }
                    $config['groupXml'] = $this->_cutXml($groupProcessor->getMainPart());
                    $groupProcessor->setMainPart('<?xml');
                    $groupProcessor->setConfig($config);
                    $parentConfig['subgroups'][$propertyName] = $groupProcessor;
                } else {
                    throw new Tinebase_Exception('find&replace block failed after subgroup was found: ' . $foundGroup);
                }
            } elseif (null !== $foundRecord) {
                if (null === ($recordProcessor = $this->_findAndReplaceSubRecord($_templateProcessor, $foundRecord))) {
                    throw new Tinebase_Exception('subrecord block failed: ' . $foundRecord);
                }
                list(,$propertyName) = explode('_', $foundRecord);
                $parentConfig['subrecords'][$propertyName] = $recordProcessor;
            } else {
                break;
            }
        } while (true);

        $config = $_templateProcessor->getConfig();
        if (count($parentConfig['subgroups']) > 0) {
            $config['subgroups'] = $parentConfig['subgroups'];
        }
        if (count($parentConfig['subrecords']) > 0) {
            $config['subrecords'] = $parentConfig['subrecords'];
        }
        $_templateProcessor->setConfig($config);
    }

    /**
     * @param Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor
     * @param string $_name
     * @return null|Tinebase_Export_Richtext_TemplateProcessor
     * @throws Tinebase_Exception
     */
    protected function _findAndReplaceSubRecord(Tinebase_Export_Richtext_TemplateProcessor $_templateProcessor, $_name = null)
    {
        if (null === ($foundRecord = $_name)) {
            foreach ($_templateProcessor->getVariables() as $var) {
                if (null === $foundRecord && strpos($var, 'SUBRECORD') === 0) {
                    $foundRecord = $var;
                    break;
                }
            }
            if (null === $foundRecord) {
                return null;
            }
        }

        if (null !== ($recordBlock = $_templateProcessor->findBlock($foundRecord, '${R' . $foundRecord . '}'))) {
            list(,$propertyName) = explode('_', $foundRecord);
            $processor = new Tinebase_Export_Richtext_TemplateProcessor('<?xml', true,
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBRECORD, $_templateProcessor);
            $config = array(
                'recordXml'     => $recordBlock,
                'name'          => $foundRecord,
            );

            if (null !== ($recordHeader = $_templateProcessor->findBlock('SUBR_HEADER_' . $propertyName, ''))) {
                $config['header'] = $recordHeader;
            }

            if (null !== ($recordFooter = $_templateProcessor->findBlock('SUBR_FOOTER_' . $propertyName, ''))) {
                $config['footer'] = $recordFooter;
            }

            if (null !== ($recordSeparator = $_templateProcessor->findBlock('SUBR_SEPARATOR_' . $propertyName, ''))) {
                $config['separator'] = $recordSeparator;
            }
            $processor->setConfig($config);
            return $processor;
        } else {
            throw new Tinebase_Exception('find&replace block failed after subrecord was found: ' . $foundRecord);
        }
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

    /**
     * @param $to
     * @param null $from
     * @throws Tinebase_Exception_NotFound
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
