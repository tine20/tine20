<?php
/**
 * Tine 2.0
 *
 * @package     Events
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for EventsItems
 *
 * @package     Events
 * @subpackage  Backend
 */
class Events_Backend_Event extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'events_event';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Events_Model_Event';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /************************ overwritten functions *******************/  
    
    /************************ helper functions ************************/
}
