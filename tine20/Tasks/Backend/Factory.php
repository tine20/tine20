<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Factory for Tasks Backends
 * 
 * @package Tasks
 */
class Tasks_Backend_Factory
{

    /**
     * Tasks 2.0 Sql backend
     */
    const SQL = 'Sql';

    /**
     * Ical backend
     */
    //const ICAL = 'Ical';
    
    /**
     * Singelton store for backends
     *
     * @var array array of Tinebase_Application_Backend_Interface
     */
    private static $_instances = array();
    
    /**
     * Factory function to return a requested Tasks backend
     *
     * @param string $_type
     * @return Tinebase_Application_Backend_Interface
     */
    static public function factory($_type)
    {
        $className = 'Tasks_Backend_' . $_type;
        
        if (isset(self::$_instances[$_type]) && self::$_instances[$_type] instanceof $className) {
            return self::$_instances[$_type];
        } else {
            self::$_instances[$_type] = new $className();
            return self::$_instances[$_type];
        }
    }
}