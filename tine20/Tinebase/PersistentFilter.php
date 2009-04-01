<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Timesheet.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add account id to sql statements to check acl
 * 
 */


/**
 * backend for persistent filters
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Tinebase_PersistentFilter extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'filter';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_PersistentFilter';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
}
