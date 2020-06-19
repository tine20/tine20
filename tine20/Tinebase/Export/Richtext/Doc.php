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

class Tinebase_Export_Richtext_Doc extends Tinebase_Export_AbstractDeprecated implements Tinebase_Record_IteratableInterface {
    
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
    public function getDownloadFilename($_appName = null, $_format = null)
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
        $this->_resolveRecords($_records);

        foreach($_records as $idx => $record) {
            $this->processRecord($record, $idx);
        }
    }

    /**
     * export single record
     *
     * @TODO: split for template / non template
     */
    public function processRecord($record, $idx)
    {
        $idx = $idx+1;
        $templateProcessor = $this->_docTemplate;

        // set all fields available
        foreach($record->getFields() as $property) {
            $value = $record->{$property};
            $fieldConfig = $this->getFieldConfig($property);

            if (is_null($value)) {
                $value = '';
            }

            if ($value instanceof DateTime) {
                $value = Tinebase_Translation::dateToStringInTzAndLocaleFormat($value, null, null, $this->_config->datetimeformat);
            }

            if (is_object($value) && method_exists($value, '__toString')) {
                $value = $value->__toString();
            }

            if (is_scalar($value)) {
                if ($fieldConfig && isset($fieldConfig->replace->pattern)) {
                    $value = preg_replace($fieldConfig->replace->pattern, $fieldConfig->replace->replacement, $value);
                }
                $templateProcessor->setValue($property . '#' . $idx, $value, 1);
            }
        }

        // go through custom column configurations
        for ($i = 0; $i < $this->_config->columns->column->count(); $i++) {
            $column = $this->_config->columns->column->{$i};

            // @TODO value should be generated in Export_Abstract
            if (in_array($column->identifier, $this->_getCustomFieldNames())) {
                // add custom fields
                if (isset($record->customfields[$column->identifier])) {
                    $value = $record->customfields[$column->identifier]['name'];
                } else {
                    $value = '';
                }
            } else {
                $value = $record->{$column->identifier};
            }
            if ($value instanceof DateTime) {
                $value = Tinebase_Translation::dateToStringInTzAndLocaleFormat($value, null, null, $column->format);
            }

            if (isset($column->replace->pattern)) {
                $value = preg_replace($column->replace->pattern, $column->replace->replacement, $value);
            }
            $templateProcessor->setValue($column->header.'#'.$idx, $value, 1);
        }
    }

    /**
     * set generic data
     *
     * @param array $result
     */
    protected function _onAfterExportRecords($result)
    {
        $this->getDocument()->setValue('export_time', Tinebase_Translation::dateToStringInTzAndLocaleFormat(Tinebase_DateTime::now(), null, null, $this->_config->timeformat));
        $this->getDocument()->setValue('export_date', Tinebase_Translation::dateToStringInTzAndLocaleFormat(Tinebase_DateTime::now(), null, null, $this->_config->dateformat));
        $this->getDocument()->setValue('export_account', Tinebase_Core::getUser()->accountDisplayName);
        $this->getDocument()->setValue('export_account_n_given', Tinebase_Core::getUser()->accountFirstName);
        $this->getDocument()->setValue('export_account_n_family', Tinebase_Core::getUser()->accountLastName);
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