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
    /**
     * Content of document rels (in XML format) of the temporary document.
     *
     * @var string
     */
    protected $_temporaryDocumentRels = null;

    protected $_tempHeaderRels = array();

    protected $_tempFooterRels = array();


    /**
     * @param string $documentTemplate The fully qualified template filename.
     *
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     */
    public function __construct($documentTemplate)
    {
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
}