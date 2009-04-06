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
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
    {
        $_record->uid = Tinebase_Record_Abstract::generateUID();
        $_record->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record          the update record
     * @param   Tinebase_Record_Interface $_currentRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_currentRecord)
    {
        // if dtstart of an event changes, we update the originator_tz
        if (! $_currentRecord->dtstart->equals($_record->dtstart)) {
            $_record->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        }
    }
    
}
