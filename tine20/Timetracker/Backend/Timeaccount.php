<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */


/**
 * backend for Timeaccounts
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Backend_Timeaccount extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_modlogActive = TRUE;
        parent::__construct(SQL_TABLE_PREFIX . 'timetracker_timeaccount', 'Timetracker_Model_Timeaccount');
    }
    
    /************************ overwritten functions *******************/  
    
    /************************ helper functions ************************/
}
