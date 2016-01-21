<?php
/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage    Export
 */

class Tinebase_Export_Richtext_Doc extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface {
    
    /**
     * the document
     * 
     * @var \PhpOffice\PhpWord\PhpWord
     */
    protected $_docObject;
    
    /**
     * the template to work on
     * 
     * @var \PhpOffice\PhpWord\TemplateProcessor
     */
    protected $_docTemplate;
    
    /**
     * format strings
     *
     * @var string
     */
    protected $_format = 'docx';
    
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
     */
    public function getDownloadFilename($_appName, $_format)
    {
        return 'letter_' . strtolower($_appName) . '.docx';
    }
    
    /**
     * generate export
     *
     * @return mixed filename/generated object/...
     */
    public function generate()
    {
        $this->_createDocument();
        $this->_exportRecords();

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

    public function save($filename) {
        $document = $this->getDocument();
        $tempfile = $document->save();

        copy($tempfile, $filename);
        unlink($tempfile);
    }

    /**
     * add body rows
     *
     * @param Tinebase_Record_RecordSet $records
     */
    public function processIteration($_records)
    {
        $record = $_records->getFirstRecord();
        
        $converter = Tinebase_Convert_Factory::factory($record);
        $resolved = $converter->fromTine20Model($record);
        
        foreach ($this->_config->properties->prop as $prop) {
            $property = (string) $prop;
            $this->_docTemplate->setValue($property, (isset($resolved[$property]) ? htmlspecialchars($resolved[$property]) : ''));
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
        
        if ($templateFile !== NULL) {
            $this->_docTemplate = new \PhpOffice\PhpWord\TemplateProcessor($templateFile);
        }
    }
}