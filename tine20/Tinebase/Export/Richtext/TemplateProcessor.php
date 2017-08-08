<?php
/**
 * Tinebase Doc/Docx template processor class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Doc/Docx template processor class
 *
 * @package     Tinebase
 * @subpackage    Export
 */


class Tinebase_Export_Richtext_TemplateProcessor extends \PhpOffice\PhpWord\TemplateProcessor
{
    const TYPE_STANDARD = 'standard';
    const TYPE_DATASOURCE = 'datasource';
    const TYPE_GROUP = 'group';
    const TYPE_SUBGROUP = 'subgroup';
    const TYPE_RECORD = 'record';
    const TYPE_SUBRECORD = 'subrecord';

    /**
     * Content of document rels (in XML format) of the temporary document.
     *
     * @var string
     */
    protected $_temporaryDocumentRels = null;

    protected $_tempHeaderRels = array();

    protected $_tempFooterRels = array();

    protected $_type = null;

    protected $_parent = null;

    protected $_config = array();

    /**
     * @param string $documentTemplate The fully qualified template filename.
     * @param bool   $inMemory
     * @param string $type
     * @param Tinebase_Export_Richtext_TemplateProcessor|null $parent
     *
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     */
    public function __construct($documentTemplate, $inMemory = false, $type = self::TYPE_STANDARD, Tinebase_Export_Richtext_TemplateProcessor $parent = null)
    {
        $this->_type = $type;
        $this->_parent = $parent;

        if (true === $inMemory) {
            $this->tempDocumentMainPart = $documentTemplate;
            return;
        }

        parent::__construct($documentTemplate);

        $index = 1;
        while (false !== $this->zipClass->locateName($this->getHeaderName($index))) {
            $fileName = 'word/_rels/header' . $index . '.xml.rels';
            if (false !== $this->zipClass->locateName($fileName)) {
                $this->_tempHeaderRels[$index] = $this->fixBrokenMacros(
                    $this->zipClass->getFromName($fileName)
                );
            }
            $index++;
        }
        $index = 1;
        while (false !== $this->zipClass->locateName($this->getFooterName($index))) {
            $fileName = 'word/_rels/footer' . $index . '.xml.rels';
            if (false !== $this->zipClass->locateName($fileName)) {
                $this->_tempFooterRels[$index] = $this->fixBrokenMacros(
                    $this->zipClass->getFromName($fileName)
                );
            }
            $index++;
        }

        if (false !== $this->zipClass->locateName('word/_rels/document.xml.rels')) {
            $this->_temporaryDocumentRels = $this->fixBrokenMacros(
                $this->zipClass->getFromName('word/_rels/document.xml.rels'));
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @return Tinebase_Export_Richtext_TemplateProcessor|null
     */
    public function getParent()
    {
        return $this->_parent;
    }

    public function replaceTine20ImagePaths()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' replacing images...');

        if (null !== $this->_temporaryDocumentRels) {
            $this->_replaceTine20ImagePaths($this->tempDocumentMainPart, $this->_temporaryDocumentRels);
        }
        foreach($this->_tempHeaderRels as $index => $data) {
            $this->_replaceTine20ImagePaths($this->tempDocumentHeaders[$index], $data);
        }
        foreach($this->_tempFooterRels as $index => $data) {
            $this->_replaceTine20ImagePaths($this->tempDocumentFooters[$index], $data);
        }
    }

    protected function _replaceTine20ImagePaths($xmlData, $relData)
    {
        if (preg_match_all('#<wp:docPr[^>]+"(\w+://[^"]+)".*?r:embed="([^"]+)"#is', $xmlData, $matches, PREG_SET_ORDER)) {
            foreach($matches as $match) {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' found url: ' . $match[1]);

                if (!in_array(mb_strtolower(pathinfo($match[1], PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'png', 'gif'))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' unsupported file extension: ' . $match[1]);
                    continue;
                }
                if (preg_match('#Relationship Id="' . $match[2] . '"[^>]+Target="(media/[^"]+)"#', $relData, $relMatch)) {
                    $fileContent = file_get_contents($match[1]);
                    if (!empty($fileContent)) {
                        $this->zipClass->deleteName('word/' . $relMatch[1]);
                        $this->zipClass->addFromString('word/' . $relMatch[1], $fileContent);
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                            Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ . ' could not get file content: ' . $match[1]);
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' could not find relation matching found url: ' . $match[1]);
                }
            }
        }
    }

    /**
     * Saves the result document.
     *
     * @return string
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function save()
    {
        if (null !== $this->_temporaryDocumentRels) {
            $this->zipClass->addFromString('word/_rels/document.xml.rels', $this->_temporaryDocumentRels);
        }

        foreach($this->_tempHeaderRels as $index => $data) {
            $this->zipClass->addFromString('word/_rels/header' . $index . '.xml.rels', $data);
        }

        foreach($this->_tempFooterRels as $index => $data) {
            $this->zipClass->addFromString('word/_rels/footer' . $index . '.xml.rels', $data);
        }

        return parent::save();
    }

    /**
     * @param string $data
     */
    public function setMainPart($data)
    {
        $this->tempDocumentMainPart = $data;
    }

    /**
     * @return string
     */
    public function getMainPart()
    {
        return $this->tempDocumentMainPart;
    }

    /**
     * replace a table row in a template document and return replaced row
     *
     * @param string $search
     * @param string $replacement
     *
     * @return string
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function replaceRow($search, $replacement)
    {
        if ('${' !== substr($search, 0, 2) && '}' !== substr($search, -1)) {
            $search = '${' . $search . '}';
        }

        $tagPos = strpos($this->tempDocumentMainPart, $search);
        if (!$tagPos) {
            throw new \PhpOffice\PhpWord\Exception\Exception("Can not clone row, template variable not found or variable contains markup.");
        }

        $rowStart = $this->findRowStart($tagPos);
        $rowEnd = $this->findRowEnd($tagPos);
        $xmlRow = $this->getSlice($rowStart, $rowEnd);

        $result = $this->getSlice(0, $rowStart) . $replacement;
        $result .= $this->getSlice($rowEnd);

        $this->tempDocumentMainPart = $result;

        return $xmlRow;
    }

    /**
     * @param $data
     */
    public function append($data)
    {
        $this->tempDocumentMainPart .= $data;
    }

    /**
     * @param string $row
     * @param int $num
     * @param string $where
     *
    public function insertRow($row, $num, $where)
    {
        $row = preg_replace('/\$\{(.*?)\}/', '\${\\1#' . $num . '}', $row);

        $this->setValue($where, $row . $where);
    }*/

    /**
     * Clone a block.
     *
     * @param string $blockname
     * @param integer $clones
     * @param boolean $replace
     *
     * @return string|null
     */
    public function cloneBlock($blockname, $clones = 1, $replace = true)
    {
        $xmlBlock = null;
        preg_match(
            '/(<\?xml.*?)(<w:p>.*\${' . $blockname . '}.*?<\/w:p>)(.*)(<w:p>.*?\${\/' . $blockname . '}.*?<\/w:p>)/is',
            $this->tempDocumentMainPart,
            $matches
        );

        if (isset($matches[3])) {
            $xmlBlock = $matches[3];
            $cloned = array();
            for ($i = 1; $i <= $clones; $i++) {
                $cloned[] = $xmlBlock;
            }

            if ($replace) {
                if (($pos = strrpos($matches[2], '<w:p>')) !== 0) {
                    $matches[2] = substr($matches[2], $pos);
                }
                $this->tempDocumentMainPart = str_replace(
                    $matches[2] . $matches[3] . $matches[4],
                    implode('', $cloned),
                    $this->tempDocumentMainPart
                );
            }
        }

        return $xmlBlock;
    }

    /**
     * Replace a block.
     *
     * @param string $blockname
     * @param string $replacement
     *
     * @return void
     */
    public function replaceBlock($blockname, $replacement)
    {
        preg_match(
            '/(<\?xml.*)(<w:p( [^>]+)?>.*\${' . $blockname . '}<\/w:.*?p>)(.*)(<w:p( [^>]+)?>.*\${\/' . $blockname . '}<\/w:.*?p>)/is',
            $this->tempDocumentMainPart,
            $matches
        );

        if (isset($matches[4])) {
            $this->tempDocumentMainPart = str_replace(
                $matches[2] . $matches[4] . $matches[5],
                $replacement,
                $this->tempDocumentMainPart
            );
        }
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;
        if (Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD === $this->_type &&
                isset($this->_config['recordXml'])) {

        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasConfig($key)
    {
        return isset($this->_config[$key]);
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getConfig($key = null)
    {
        if (null === $key) {
            return $this->_config;
        }
        return $this->_config[$key];
    }

    /**
     * Returns array of all variables in template.
     *
     * @return string[]
     */
    public function getVariables()
    {
        $result = parent::getVariables();

        switch($this->_type) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_DATASOURCE:
                if (isset($this->_config['group'])) {
                    $result = array_merge($result, $this->_config['group']->getVariables());
                }
            case self::TYPE_GROUP:
                if (isset($this->_config['record'])) {
                    $result = array_merge($result, $this->_config['record']->getVariables());
                }
                if (isset($this->_config['recordRow']) && isset($this->_config['recordRow']['recordRowProcessor'])) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $result = array_merge($result, $this->_config['recordRow']['recordRowProcessor']->getVariables());
                }
                // DO NOT return variables of sub groups or sub records
                break;
            case self::TYPE_SUBGROUP:
            case self::TYPE_SUBRECORD:
                if (isset($this->_config['recordXml'])) {
                    $result = array_merge($result, $this->getVariablesForPart($this->_config['recordXml']));
                }
                break;
        }

        return array_unique($result);
    }
}