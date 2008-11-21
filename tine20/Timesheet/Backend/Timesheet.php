<?php
/**
 * Tine 2.0
 *
 * @package     Timesheet
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * backend for timesheets
 *
 * @package     Timesheet
 * @subpackage  Backend
 */
class Timesheet_Backend_Timesheet extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'timesheet', 'Timesheet_Model_Timesheet');
    }

    /************************ helper functions ************************/

    /**
     * add the fields to search for to the query
     *
     * @param   Zend_Db_Select           $_select current where filter
     * @param   Timesheet_Model_TimesheetFilter  $_filter the string to search for
     */
    protected function _addFilter(Zend_Db_Select $_select, Timesheet_Model_TimesheetFilter $_filter)
    {
        $_filter->appendFilterSql($_select);
    }
}
