<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for Courses, does event handling
 *
 * @package     Courses
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Controller.php 10423 2009-09-15 08:03:09Z c.weiss@metaways.de $
 * 
 */

/**
 * main controller for Courses
 *
 * @package     Courses
 * @subpackage  Controller
 */
class Courses_Controller extends Tinebase_Controller_Abstract implements Tinebase_Event_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Courses_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
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
     * @return Courses_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Courses_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    public function handleEvents(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        if ($this->_disabledEvents === true) {
            // nothing todo
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' events are disabled. do nothing'
            );
            return;
        }
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_BeforeDeleteGroup':
                // remove courses
                Courses_Controller_Course::getInstance()->deleteByFilter(new Courses_Model_CourseFilter(array(array(
                    'field' => 'group_id', 'operator' => 'in', 'value' => $_eventObject->groupIds
                ))));
                break;
        }
    }
}
