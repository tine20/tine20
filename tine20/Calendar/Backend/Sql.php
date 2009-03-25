<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * native tine 2.0 events sql backend
 *
 * Events consists of the properties of Calendar_Model_Evnet except Tags and Notes 
 * which are as always handles by their controllers/backends
 * 
 *  
 */
class Calendar_Backend_Sql extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    
}