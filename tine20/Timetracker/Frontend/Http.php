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
     * @param string $_format 
     */
    public function exportTimesheets($_filter, $_format)
    {
        $filter = new Timetracker_Model_TimesheetFilter(Zend_Json::decode($_filter));
        parent::_export($filter, $_format);
    }

    /**
     * export records matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_format only csv implemented
     */
    public function exportTimeaccounts($_filter, $_format)
    {
        $filter = new Timetracker_Model_TimeaccountFilter(Zend_Json::decode($_filter));
        parent::_export($filter, $_format);
    }
}
