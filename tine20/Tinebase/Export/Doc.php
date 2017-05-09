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

class Tinebase_Export_Doc extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface {

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

    protected $_rowCount = 0;

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

    /**
     * generate export
     */
    public function generate()
    {
        $this->_rowCount = 0;
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

    protected function _startRow()
    {
        $this->_rowCount += 1;
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

        if ($templateFile !== NULL) {
            $this->_docTemplate = new Tinebase_Export_Richtext_TemplateProcessor($templateFile);
        }
    }

    /**
     * @param string $_key
     * @param string $_value
     */
    protected function _setValue($_key, $_value)
    {
        if (true === $this->_iterationDone) {
            $this->_docTemplate->setValue($_key, $_value);
        } else {
            $this->_docTemplate->setValue($_key . '#' . $this->_rowCount, $_value);
        }
    }

    /**
     * @param string $_value
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _writeValue($_value)
    {
        throw new Tinebase_Exception_NotImplemented(__CLASS__ . ' can not provide a meaningful default implementation. Subclass needs to provide or avoid it from being called');
    }

    public function _getTwigSource()
    {
        $i = 0;
        $source = '[';
        foreach ($this->_docTemplate->getVariables() as $placeholder) {
            if (strpos($placeholder, 'twig:') === 0) {
                $this->_twigMapping[$i] = $placeholder;
                $source .= ($i === 0 ? '' : ',') . '"{{' . substr($placeholder, 5) . '}}"';
                ++$i;
            }
        }
        $source .= ']';

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' returning twig template source: ' . $source);

        return $source;
    }
}