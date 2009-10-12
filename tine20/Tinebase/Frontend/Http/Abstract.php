<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http interface in tine 2.0 is used to generate the base layouts
 * in new browser windows. 
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Frontend_Http_Abstract extends Tinebase_Frontend_Abstract implements Tinebase_Frontend_Http_Interface
{
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        $standardFile = "{$this->_applicationName}/js/{$this->_applicationName}.js";
        if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . "/$standardFile")) {
            return array($standardFile);
        }
        return array();
        
    }
    
    /**
     * Returns all CSS files which must be inclued for this app
     *
     * @return array Array of filenames
     */
    public function getCssFilesToInclude()
    {
        $standardFile = "{$this->_applicationName}/css/{$this->_applicationName}.css";
        if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . "/$standardFile")) {
            return array($standardFile);
        }
        return array();
    }
        
    /**
     * generic export function
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_format
     * @param Tinebase_Controller_Record_Abstract $_controller
     * @return void
     * 
     * @todo add export interface for $export object with generate() method?
     * @todo support single ids as filter?
     * @todo use stream here instead of temp file?
     */
    protected function _export(Tinebase_Model_Filter_FilterGroup $_filter, $_format, Tinebase_Controller_Record_Abstract $_controller = NULL)
    { 
        // create export object
        $exportClass = $_filter->getApplicationName() . '_Export_' . ucfirst(strtolower($_format));
        if (! class_exists($exportClass)) {
            throw new Tinebase_Exception_NotFound('No ' . $_format . ' export class found for ' . $_filter->getApplicationName());
        }
        $export = new $exportClass();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exporting ' . $_filter->getModelName() . ' in format ' . $_format);

        switch ($_format) {
            case 'pdf':
                
                /*
                if (is_array($decodedFilter)) {
                    $filter = new Addressbook_Model_ContactFilter($decodedFilter);
                    $paging = new Tinebase_Model_Pagination();
                    $contactIds = Addressbook_Controller_Contact::getInstance()->search($filter, $paging, false, true);                
                } else {
                    $contactIds = (array) $decodedFilter;
                }
                */
                
                // get ids by filter
                $ids = $_controller->search($_filter, NULL, FALSE, TRUE);
                
                // loop records
                foreach ($ids as $id) {
                    if (! empty($id)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating pdf for ' . $_filter->getModelName() . '  id ' . $id);
                        $record = $_controller->get($id);
                        $export->generate($record);
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $_filter->getModelName() . ' id empty!');
                    }
                }
                    
                // render pdf
                try {
                    $pdfOutput = $export->render();
                } catch (Zend_Pdf_Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
                    exit;
                }
                
                $contentType = 'application/x-pdf';
                break;
                
            case 'csv':
                $result = $export->generate($_filter);
                $contentType = 'text/csv';
                break;

            case 'ods':
                $result = $export->generate($_filter);
                $contentType = 'application/vnd.oasis.opendocument.spreadsheet';
                break;
                
            default:
                throw new Tinebase_Exception_UnexpectedValue('Format ' . $_format . ' not supported.');
        }

        // write headers
        $filename = 'tine20_export_' . strtolower($_filter->getApplicationName()) . '.' . $_format;
        header("Pragma: public");
        header("Cache-Control: max-age=0");
        header("Content-Disposition: " . (($_format == 'pdf') ? 'inline' : 'attachment') . '; filename=' . $filename);
        header("Content-Description: $_format File");  
        header("Content-type: $contentType");
        
        // output export file
        if ($_format == 'pdf') {
            echo $pdfOutput;
        } else {
            readfile($result);
            unlink($result);
        }
    }        
    
    /**
     * Helper function to coerce browsers to reload js files when changed.
     *
     * @param string $_file
     * @return string file
     */
    public static function _appendFileTime( $_file )
    {
        $path = dirname(dirname(dirname(dirname(__FILE__)))) . "/$_file";
        return "$_file?". @filectime($path);
    }
}
