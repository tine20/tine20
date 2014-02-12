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
     * @var PHPWord
     */
    protected $_docObject;
    
    /**
     * the template to work on
     * 
     * @var PHPWord_Template
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
        $tempFile = Tinebase_Core::guessTempDir() . DIRECTORY_SEPARATOR . Tinebase_Record_Abstract::generateUID() . '.docx';
        $this->getDocument()->save($tempFile);
        readfile($tempFile);
        unlink($tempFile);
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
            // @TODO: remove the utf8_decode here when PHPWord_Template does not convert to utf8 anymore.
            //        the htmlspecialchars shouldn't be required, this should have been done by the PHPWord Library
            $this->_docTemplate->setValue($property, (isset($resolved[$property]) ? utf8_decode(htmlspecialchars($resolved[$property])) : ''));
        }
    }
    
    /**
     * get word object
     *
     * @return PHPExcel
     */
    public function getDocument()
    {
        return $this->_docTemplate ? $this->_docTemplate : $this->_docObject;
    }

    
    /**
     * create new excel document
     *
     * @return void
     */
    protected function _createDocument()
    {
        // this looks stupid, but the PHPDoc library is beta, so this is needed. otherwise the lib would create temp files in the template folder ;(
        $templateFile = $this->_getTemplateFilename();
        $tempTemplateFile = Tinebase_Core::guessTempDir() . DIRECTORY_SEPARATOR . Tinebase_Record_Abstract::generateUID() . '.docx';
        copy($templateFile, $tempTemplateFile);
        $this->_docObject = new PHPWord();
        
        if ($templateFile !== NULL) {
            $this->_docTemplate = $this->_docObject->loadTemplate($tempTemplateFile);
        }
        
        unlink($tempTemplateFile);
    }
}