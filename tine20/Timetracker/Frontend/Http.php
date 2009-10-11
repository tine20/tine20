<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Http.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */

/**
 * This class handles all Http requests for the Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Frontend
 */
class Timetracker_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'Timetracker';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Timetracker/js/DurationSpinner.js',
            'Timetracker/js/Models.js',
            'Timetracker/js/Timetracker.js',
            'Timetracker/js/MainScreen.js',
            'Timetracker/js/TimeaccountSelect.js',
            'Timetracker/js/TimeaccountGridPanel.js',
            'Timetracker/js/TimeaccountEditDialog.js',
            'Timetracker/js/TimesheetGridPanel.js',
            'Timetracker/js/TimesheetEditDialog.js',
        );
    }

    /**
     * export records matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_format only csv implemented
     * 
     * @todo use generic _export() function
     */
    public function exportTimesheets($_filter, $_format)
    {
        $decodedFilter = Zend_Json::decode($_filter);
        $filter = new Timetracker_Model_TimesheetFilter($decodedFilter);
        
        switch($_format) {
            case 'csv':
                $csvExportClass = new Timetracker_Export_Csv();        
                $result = $csvExportClass->exportTimesheets($filter);
                $contentType = 'text/csv';
                break;
            case 'ods':
                $odsExportClass = new Timetracker_Export_Ods();
                $result = $odsExportClass->exportTimesheets($filter);
                $contentType = 'application/vnd.oasis.opendocument.spreadsheet';
                break;
            default:
                throw new Timetracker_Exception_UnexpectedValue('Format ' . $_format . ' not supported yet.');
        }
        
        header("Pragma: public");
        header("Cache-Control: max-age=0");
        header("Content-Disposition: attachment; filename=timesheet_export." . $_format);
        header("Content-Description: $_format File");  
        header("Content-type: $contentType"); 
        readfile($result);
        
        unlink($result);
        exit;
    }

    /**
     * export records matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_format only csv implemented
     * 
     * @todo use stream here instead of temp file
     * @todo generalize this
     */
    public function exportTimeaccounts($_filter, $_format)
    {
        $decodedFilter = Zend_Json::decode($_filter);
        $filter = new Timetracker_Model_TimeaccountFilter($decodedFilter);
        
        switch($_format) {
            /*
            case 'csv':
                $csvExportClass = new Timetracker_Export_Csv();        
                $result = $csvExportClass->exportTimeaccounts($filter);
                $contentType = 'text/csv';
                break;
            */
            case 'ods':
                $odsExportClass = new Timetracker_Export_Ods();
                $result = $odsExportClass->exportTimeaccounts($filter);
                $contentType = 'application/vnd.oasis.opendocument.spreadsheet';
                break;
            default:
                throw new Timetracker_Exception_UnexpectedValue('Format ' . $_format . ' not supported yet.');
        }
        
        $filename = 'timeaccount_export.' . $_format;
        
        header("Pragma: public");
        header("Cache-Control: max-age=0");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Description: $_format File");  
        header("Content-type: $contentType"); 
        readfile($result);
        
        unlink($result);
        exit;
    }
}
