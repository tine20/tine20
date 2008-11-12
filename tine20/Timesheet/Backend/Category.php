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
 * backend for categories
 *
 * @package     Timesheet
 * @subpackage  Backend
 */
class Timesheet_Backend_Category extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'timesheet_category', 'Timesheet_Model_Category');
    }

    /************************ helper functions ************************/
}
