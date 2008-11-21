<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * backend for timesheets
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Backend_Timesheet extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'timetracker_timesheet', 'Timetracker_Model_Timesheet');
    }

    /************************ helper functions ************************/

    /**
     * add the fields to search for to the query
     *
     * @param   Zend_Db_Select           $_select current where filter
     * @param   Timetracker_Model_TimesheetFilter  $_filter the string to search for
     */
    protected function _addFilter(Zend_Db_Select $_select, Timetracker_Model_TimesheetFilter $_filter)
    {
        $_filter->appendFilterSql($_select);
    }
}
