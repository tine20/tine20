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
    // add fns for participats state settings -> move to attendee controller?
    // add group attendee handling -> move to attendee controller?
    //
    // add handling to fetch all exceptions of a given event set (ActiveSync Frontend)
    //
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
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        // if dtstart of an event changes, we update the originator_tz
        if (! $_oldRecord->dtstart->equals($_record->dtstart)) {
            $_record->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        }
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (    !$this->_doContainerACLChecks 
            ||  !$_record->has('container_id') 
            // admin grant includes all others
            ||  $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADMIN)) {
            return TRUE;
        }

        $hasGrant = FALSE;
        
        $currentAccountId = $this->_currentAccount->getId();
        
        switch ($_action) {
            case 'get':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_READ)
                            || $_record->organizer == $currentAccountId
                            || in_array($currentAccountId, $_record->attendee->filter('user_type', Calendar_Model_Attendee::USERTYPE_USER)->user_id)
                            || count(array_intersect(
                                   $_record->attendee->filter('user_type', Calendar_Model_Attendee::USERTYPE_GROUP)->user_id,
                                   Tinebase_Group::getInstance()->getGroupMemberships($currentAccountId)
                               )) > 0;
                break;
            case 'create':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_EDIT)
                || $_record->organizer == $currentAccountId;
                break;
            case 'delete':
                $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
                $hasGrant = ((
                    $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_DELETE)
                    || $_record->organizer == $currentAccountId
                    ) && $container->type != Tinebase_Model_Container::TYPE_INTERNAL
                );
                break;
        }
        
        if (!$hasGrant) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'No permissions to ' . $_action . ' in container ' . $_record->container_id);
            }
        }
        
        return $hasGrant;
    }
}
