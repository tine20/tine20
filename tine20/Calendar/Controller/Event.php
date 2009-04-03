<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Event Controller
 * 
 * @package Calendar
 */
class Calendar_Controller_Event extends Tinebase_Controller_Record_Abstract
{
    // todo in this controller:
    // handle organizer & organizer tz
    //
    // ensure rights:
    //   search container + implicit organizer & participant
    //   create/update participants authkey? + implict
    //            
    // add fns for participats state settings -> move to attendee controller?
    // add handling to compute recurset (JSON) -> recur model/controller (computation/cacheing)?
    // add handling to append all exceptions (AS)
    // handle alarms -> generic approach
    
    /**
     * @var Calendar_Controller_Event
     */
    private static $_instance = NULL;
    
    /**
     * @var Tinebase_Model_User
     */
    protected $_currentAccount = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Event';
        $this->_backend         = new Calendar_Backend_Sql();
        $this->_currentAccount  = Tinebase_Core::getUser();
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Controller_Event
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_Event();
        }
        return self::$_instance;
    }
    
    /****************************** overwritten functions ************************/

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_task)
    {
        if(empty($_task->class_id)) {
            $_task->class_id = NULL;
        }
        return parent::create($_task);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_task)
    {
        return parent::update($_task);
    }
    
    /**
     * ensures organizer && organizer tz
     *
     * // not easy: organizer creates event: tz is 
     * @param Calendar_Model_Event $_event
     */
    public function _handleOrganizer($_event)
    {
        if (empty(Tinebase_User::getInstance()->getUserById($_event->organizer)->getId())) {
            $_event->organizer = $this->_currentAccount->getId();
        }
        
        if ($_event->organizer == $this->_currentAccount->getId()) {
            // use session timezone?
        }
        $_event->organizer_tz = Tinebase_Config::getInstance()->getPreference($_event->organizer, Tinebase_Config::TIMEZONE);
    }
}
