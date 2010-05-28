<?php
/**
 * Calendar Event Notifications
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Event Notifications
 *
 * @package     Calendar
 */
 class Calendar_Controller_EventNotifications
 {
     const NOTIFICATION_LEVEL_NONE                      =  0;
     const NOTIFICATION_LEVEL_INVITE_CANCEL             = 10;
     const NOTIFICATION_LEVEL_EVENT_RESCHEDULE          = 20;
     const NOTIFICATION_LEVEL_EVENT_UPDATE              = 30;
     const NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE    = 40;
     
    /**
     * @var Calendar_Controller_EventNotifications
     */
    private static $_instance = NULL;
    
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
     * @return Calendar_Controller_EventNotifications
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_EventNotifications();
        }
        
        return self::$_instance;
    }
    
    /**
     * constructor
     * 
     */
    private function __construct()
    {
        
    }
    
    /**
     * get updates of human interest
     * 
     * @param  Calendar_Model_Event $_event
     * @param  Calendar_Model_Event $_oldEvent
     * @return array
     */
    protected function _getUpdates($_event, $_oldEvent)
    {
        // check event details
        $diff = $_event->diff($_oldEvent);
        
        $orderedUpdateFieldOfInterest = array(
            'dtstart', 'dtend', 'summary', 'location', 'description',
            'organizer', 'transp', 'priority', 'status_id', 'class',
            'url', 'rrule', 'is_all_day_event', 'originator_tz', /*'tags', 'notes',*/
        );
        
        $updates = array();
        foreach ($orderedUpdateFieldOfInterest as $field) {
            if (array_key_exists($field, $diff)) {
                $updates[$field] = $diff[$field];
            }
        }
        
        // check attendee updates
        $attendeeMigration = $_oldEvent->attendee->getMigration($_event->attendee->getArrayOfIds());
        foreach ($attendeeMigration['toUpdateIds'] as $key => $attenderId) {
            $currAttender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
            $oldAttender  = $_oldEvent->attendee[$_oldEvent->attendee->getIndexById($attenderId)];
            if ($currAttender->status != $oldAttender->status) {
                $attendeeMigration['toUpdateIds'][$key] = $currAttender;
            } else {
                unset($attendeeMigration['toUpdateIds'][$key]);
            }
        }
        foreach ($attendeeMigration['toCreateIds'] as $key => $attenderId) {
            $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
            $attendeeMigration['toCreateIds'][$key] = $attender;
        }
        foreach ($attendeeMigration['toDeleteIds'] as $key => $attenderId) {
            $attender = $_oldEvent->attendee[$_oldEvent->attendee->getIndexById($attenderId)];
            $attendeeMigration['toDeleteIds'][$key] = $attender;
        }
        
        $attendeeUpdates = array();
        foreach(array('toCreateIds', 'toDeleteIds', 'toUpdateIds') as $action) {
            if (! empty($attendeeMigration[$action])) {
                $attendeeUpdates[substr($action, 0, -3)] = array_values($attendeeMigration[$action]);
            }
        }
        
        if (! empty($attendeeUpdates)) {
            $updates['attendee'] = $attendeeUpdates;
        }
        
        return $updates;
    }
    
    /**
     * send notifications 
     * 
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param Calendar_Model_Event       $_oldEvent
     * @return void
     */
    public function sendNotifications($_event, $_updater, $_action, $_oldEvent=NULL)
    {
        // lets resolve attendee once as batch to fill cache
        $attendee = clone $_event->attendee;
        Calendar_Model_Attender::resolveAttendee($attendee);
        
        switch ($_action) {
            case 'alarm':
                foreach($_event->attendee as $attender) {
                    $this->sendNotificationToAttender($attender, $_event, $_updater, $_action, self::NOTIFICATION_LEVEL_NONE);
                }
                break;
            case 'created':
            case 'deleted':
                foreach($_event->attendee as $attender) {
                    $this->sendNotificationToAttender($attender, $_event, $_updater, $_action, self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                }
                break;
            case 'changed':
                $attendeeMigration = $_oldEvent->attendee->getMigration($_event->attendee->getArrayOfIds());
                
                foreach ($attendeeMigration['toCreateIds'] as $attenderId) {
                    $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
                    $this->sendNotificationToAttender($attender, $_event, $_updater, 'created', self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                }
                
                foreach ($attendeeMigration['toDeleteIds'] as $attenderId) {
                    $attender = $_oldEvent->attendee[$_oldEvent->attendee->getIndexById($attenderId)];
                    $this->sendNotificationToAttender($attender, $_oldEvent, $_updater, 'deleted', self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                }
                
                // NOTE: toUpdateIds are all attendee to be notified
                if (! empty($attendeeMigration['toUpdateIds'])) {
                    $updates = $this->_getUpdates($_event, $_oldEvent);
                    
                    if (empty($updates)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " empty update, nothing to notify about");
                        return;
                    }
                    
                    // compute change type
                    if (count(array_intersect(array('dtstart', 'dtend'), array_keys($updates))) > 0) {
                        $notificationLevel = self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE;
                    } else if (count(array_diff(array_keys($updates), array('attendee'))) > 0) {
                        $notificationLevel = self::NOTIFICATION_LEVEL_EVENT_UPDATE;
                    } else {
                        $notificationLevel = self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE;
                    }
                    
                    // send notifications
                    foreach ($attendeeMigration['toUpdateIds'] as $attenderId) {
                        $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
                        $this->sendNotificationToAttender($attender, $_event, $_updater, 'changed', $notificationLevel, $updates);
                    }
                }
                
                break;
                
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
                
        }
    }
    
    /**
     * send notification to a single attender
     * 
     * @param Calendar_Model_Attender    $_attender
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param String                     $_notificationLevel
     * @param array                      $_updates
     * @return void
     */
    public function sendNotificationToAttender($_attender, $_event, $_updater, $_action, $_notificationLevel, $_updates=NULL)
    {
        if (! in_array($_attender->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))
            // 2010-05-05 disable for the moment
            //  - for contacts prefs of organizer is taken, this leads to unpredictable results if notificaiton is send or not!
            //  - users can not yet configure which user/contact should get notificaitons or not    
            //|| ! $_attender->getResolvedUser() instanceof Addressbook_Model_Contact
        ) {
            // don't send notifications to non persons
            //   OR non resolvable attendee
            return;
        }
        
        // find organizer account
        if ($_event->organizer) {
            $organizerContact = Addressbook_Controller_Contact::getInstance()->get($_event->organizer);
            $organizer = Tinebase_User::getInstance()->getFullUserById($organizerContact->account_id);
        } else {
            // use creator as organizer
            $organizer = Tinebase_User::getInstance()->getFullUserById($_event->created_by);
        }
        
        // get prefered language, timezone and notification level
        $prefUser = $_attender->getUserAccountId();
        // e.g. contacts
        if (! $prefUser) {
            $prefUser = $organizer->getId();
        }
        $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $prefUser));
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $prefUser);
        $translate = Tinebase_Translation::getTranslation('Calendar', $locale);
        
        // check if user wants this notification
        $sendLevel          = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::NOTIFICATION_LEVEL, $prefUser);
        $sendOnOwnActions   = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS, $prefUser);
        if ($prefUser == $_updater->getId() && ! $sendOnOwnActions) {
            return;
        }
        if ($sendLevel < $_notificationLevel) {
            if($prefUser != $organizer->getId()) {
                return;
            } else if ($sendLevel == self::NOTIFICATION_LEVEL_NONE) {
                return;
            }
        }

        // get date strings
        $startDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtstart, $timezone, $locale);
        $endDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtend, $timezone, $locale);
        
        switch ($_action) {
            case 'alarm':
                $messageSubject = sprintf($translate->_('Alarm for event "%1$s" at %2$s'), $_event->summary, $startDateString);
                break;
            case 'created':
                $messageSubject = sprintf($translate->_('Event invitation "%1$s" at %2$s'), $_event->summary, $startDateString);
                break;
            case 'deleted':
                $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been canceled' ), $_event->summary, $startDateString);
                break;
            case 'changed':
                switch ($_notificationLevel) {
                    case self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE:
                        $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been rescheduled' ), $_event->summary, $startDateString);
                        break;
                        
                    case self::NOTIFICATION_LEVEL_EVENT_UPDATE:
                        $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been updated' ), $_event->summary, $startDateString);
                        break;
                        
                    case self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE:
                        if(! empty($_updates['attendee']) && ! empty($_updates['attendee']['toUpdate']) && count($_updates['attendee']['toUpdate']) == 1) {
                            // single attendee status update
                            $attender = $_updates['attendee']['toUpdate'][0];
                            
                            switch ($attender->status) {
                                case Calendar_Model_Attender::STATUS_ACCEPTED:
                                    $messageSubject = sprintf($translate->_('%1$s accepted event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                                    
                                case Calendar_Model_Attender::STATUS_DECLINED:
                                    $messageSubject = sprintf($translate->_('%1$s declined event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                                    
                                case Calendar_Model_Attender::STATUS_TENTATIVE:
                                    $messageSubject = sprintf($translate->_('Tentative response from %1$s for event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                                    
                                case Calendar_Model_Attender::STATUS_NEEDSACTION:
                                    $messageSubject = sprintf($translate->_('No response from %1$s for event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                            }
                        } else {
                            $messageSubject = sprintf($translate->_('Attendee changes for event "%1$s" at %2$s' ), $_event->summary, $startDateString);
                        }
                        break;
                }
                break;
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
        }
        
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');
        
        $view->translate    = $translate;
        $view->timezone     = $timezone;
        
        $view->event        = $_event;
        $view->updater      = $_updater;
        $view->updates      = $_updates;
        
        $messageBody = $view->render('eventNotification.php');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " receiver: '{$_attender->getEmail()}'");
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " subject: '$messageSubject'");
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " body: $messageBody");
        
        // NOTE: this is a contact as we only support users and groupmembers
        $contact = $_attender->getResolvedUser();
        $sender = $_action == 'alarm' ? $organizer : $_updater;
        Tinebase_Notification::getInstance()->send($sender, array($contact), $messageSubject, $messageBody);
    }
 }