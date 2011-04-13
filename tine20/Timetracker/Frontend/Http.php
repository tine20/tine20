<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * export records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $options format or export definition id
     */
    public function exportTimesheets($filter, $options)
    {
        $filter = new Timetracker_Model_TimesheetFilter(Zend_Json::decode($filter));
        parent::_export($filter, Zend_Json::decode($options));
    }

    /**
     * export records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $options format or export definition id
     */
    public function exportTimeaccounts($filter, $options)
    {
        $filter = new Timetracker_Model_TimeaccountFilter(Zend_Json::decode($filter));
        parent::_export($filter, Zend_Json::decode($options));
    }
}
