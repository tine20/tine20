<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * controller for ActiveSync
 *
 * @package     ActiveSync
 */
class ActiveSync_Controller extends Tinebase_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller
     */
    private static $_instance = NULL;
    
    /**
     * constructor
     */
    private function __construct() 
    {
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return ActiveSync_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ActiveSync_Controller;
        }
        
        return self::$_instance;
    }
}
