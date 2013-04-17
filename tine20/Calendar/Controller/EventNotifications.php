<?php
/**
 * Calendar Event Notifications
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
            'transp', 'priority', 'status', 'class',
            'url', 'rrule', 'is_all_day_event', 'originator_tz', /*'tags', 'notes',*/
        );
        
        $updates = array();
        foreach ($orderedUpdateFieldOfInterest as $field) {
            if (array_key_exists($field, $diff)) {
                $updates[$field] = $diff[$field];
            }
        }
        
        // cehck for organizer update
        if (Tinebase_Record_Abstract::convertId($_event['organizer'], 'Addressbook_Model_Contact') != 
            Tinebase_Record_Abstract::convertId($_oldEvent['organizer'], 'Addressbook_Model_Contact')) {
            
            $updates['organizer'] = $_event->resolveOrganizer();
        }
        
        // check attendee updates
        $attendeeMigration = Calendar_Model_Attender::getMigration($_oldEvent->attendee, $_event->attendee);
        foreach ($attendeeMigration['toUpdate'] as $attendee) {
            $oldAttendee = Calendar_Model_Attender::getAttendee($_oldEvent->attendee, $attendee);
            if ($attendee->status == $oldAttendee->status) {
                $attendeeMigration['toUpdate']->removeRecord($attendee);
            }
        }
        
        foreach($attendeeMigration as $action => $migration) {
            Calendar_Model_Attender::resolveAttendee($migration, FALSE);
            if (! count($migration)) {
                unset($attendeeMigration[$action]);
            }
        }
        
        if (! empty($attendeeMigration)) {
            $updates['attendee'] = $attendeeMigration;
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
     * @param Tinebase_Model_Alarm       $_alarm
     * @return void
     */
    public function doSendNotifications($_event, $_updater, $_action, $_oldEvent=NULL, $_alarm=NULL)
    {
        // we only send notifications to attendee
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }

        if ($_event->dtend === NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($_event->toArray(), TRUE));
            throw new Tinebase_Exception_UnexpectedValue('no dtend set in event');
        }
        
        // skip notifications to past events
        if (Tinebase_DateTime::now()->subHour(1)->isLater($_event->dtend)) {
            if ($_action == 'alarm' || ! ($_event->isRecurException() || $_event->rrule)) {
                return;
            }
        }
        
        // lets resolve attendee once as batch to fill cache
        $attendee = clone $_event->attendee;
        Calendar_Model_Attender::resolveAttendee($attendee);
        
        switch ($_action) {
            case 'alarm':
                foreach($_event->attendee as $attender) {
                    if (Calendar_Model_Attender::isAlarmForAttendee($attender, $_alarm)) {
                        $this->sendNotificationToAttender($attender, $_event, $_updater, $_action, self::NOTIFICATION_LEVEL_NONE);
                    }
                }
                break;
            case 'created':
            case 'deleted':
                foreach($_event->attendee as $attender) {
                    $this->sendNotificationToAttender($attender, $_event, $_updater, $_action, self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                }
                break;
            case 'changed':
                $attendeeMigration = Calendar_Model_Attender::getMigration($_oldEvent->attendee, $_event->attendee);
                
                foreach ($attendeeMigration['toCreate'] as $attender) {
                    $this->sendNotificationToAttender($attender, $_event, $_updater, 'created', self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                }
                
                foreach ($attendeeMigration['toDelete'] as $attender) {
                    $this->sendNotificationToAttender($attender, $_oldEvent, $_updater, 'deleted', self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                }
                
                // NOTE: toUpdate are all attendee to be notified
                if (count($attendeeMigration['toUpdate']) > 0) {
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
                    foreach ($attendeeMigration['toUpdate'] as $attender) {
                        $this->sendNotificationToAttender($attender, $_event, $_updater, 'changed', $notificationLevel, $updates);
                    }
                }
                
                break;
                
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
                
        }
        
        // SEND REPLY/COUNTER to external organizer
        if ($_event->organizer && ! $_event->resolveOrganizer()->account_id && count($_event->attendee) == 1) {
            $updates = array('attendee' => array('toUpdate' => $_event->attendee));
            $organizer = new Calendar_Model_Attender(array(
                'user_type'  => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'    => $_event->resolveOrganizer()
            ));
            $this->sendNotificationToAttender($organizer, $_event, $_updater, 'changed', self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE, $updates);
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
        try {
                
            // find organizer account
            if ($_event->organizer && $_event->resolveOrganizer()->account_id) {
                $organizer = Tinebase_User::getInstance()->getFullUserById($_event->resolveOrganizer()->account_id);
            } else {
                // use creator as organizer
                $organizer = Tinebase_User::getInstance()->getFullUserById($_event->created_by);
            }
            
            // get prefered language, timezone and notification level
            $prefUser = $_attender->getUserAccountId();
            $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $prefUser ? $prefUser : $organizer->getId()));
            $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $prefUser ? $prefUser : $organizer->getId());
            $translate = Tinebase_Translation::getTranslation('Calendar', $locale);
            
            // check if user wants this notification
            $sendLevel          = $prefUser ? Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::NOTIFICATION_LEVEL, $prefUser) : 100;
            $sendOnOwnActions   = $prefUser ? Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS, $prefUser) : 0;
            
            // NOTE: organizer gets mails unless she set notificationlevel to NONE
            if (($prefUser == $_updater->getId() && ! $sendOnOwnActions) || ($sendLevel < $_notificationLevel && ($prefUser != $organizer->getId() || $sendLevel == self::NOTIFICATION_LEVEL_NONE))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " preferred notification loevel not reached -> skipping notification for {$_attender->getEmail()}");
                return;
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
                    $method = Calendar_Model_iMIP::METHOD_REQUEST;
                    break;
                case 'deleted':
                    $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been canceled' ), $_event->summary, $startDateString);
                    $method = Calendar_Model_iMIP::METHOD_CANCEL;
                    break;
                case 'changed':
                    switch ($_notificationLevel) {
                        case self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE:
                            if (array_key_exists('dtstart', $_updates)) {
                                $oldStartDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_updates['dtstart'], $timezone, $locale);
                                $messageSubject = sprintf($translate->_('Event "%1$s" has been rescheduled from %2$s to %3$s' ), $_event->summary, $oldStartDateString, $startDateString);
                                $method = Calendar_Model_iMIP::METHOD_REQUEST;
                                break;
                            }
                            // fallthrough if dtstart didn't change
                            
                        case self::NOTIFICATION_LEVEL_EVENT_UPDATE:
                            $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been updated' ), $_event->summary, $startDateString);
                            $method = Calendar_Model_iMIP::METHOD_REQUEST;
                            break;
                            
                        case self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE:
                            if(! empty($_updates['attendee']) && ! empty($_updates['attendee']['toUpdate']) && count($_updates['attendee']['toUpdate']) == 1) {
                                // single attendee status update
                                $attender = $_updates['attendee']['toUpdate']->getFirstRecord();
                                
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
                            
                            // we don't send iMIP parts to organizers with an account cause event is already up to date
                            if ($_event->organizer && !$_event->resolveOrganizer()->account_id) {
                                $method = Calendar_Model_iMIP::METHOD_REPLY;
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
            
            if (isset($method) && version_compare(PHP_VERSION, '5.3.0', '>=')) {
                $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
                $converter->setMethod($method);
                $vcalendar = $converter->fromTine20Model($_event);
                
                // in Tine 2.0 non organizers might be given the grant to update events
                // @see rfc6047 section 2.2.1 & rfc5545 section 3.2.18
                if ($method != Calendar_Model_iMIP::METHOD_REPLY && $_event->organizer !== $_updater->contact_id) {
                    foreach($vcalendar->children() as $component) {
                        if ($component->name == 'VEVENT') {
                            if (isset($component->{'ORGANIZER'})) {
                                $component->{'ORGANIZER'}->add(new  Sabre_VObject_Parameter('SEND-BY', 'mailto:' . $_updater->accountEmailAddress));
                            }
                        }
                    }
                }
                
                // @TODO in Tine 2.0 status updater might not be updater
                if ($method == Calendar_Model_iMIP::METHOD_REPLY) {
                    foreach($vcalendar->children() as $component) {
                        if ($component->name == 'VEVENT') {
                            $component->{'REQUEST-STATUS'} = '2.0;Success';
                        }
                    }
                }
                
                $calendarPart           = new Zend_Mime_Part($vcalendar->serialize());
                $calendarPart->charset  = 'UTF-8';
                $calendarPart->type     = 'text/calendar; method=' . $method;
                $calendarPart->encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE;
                
                $attachment = new Zend_Mime_Part($vcalendar->serialize());
                $attachment->type     = 'application/ics';
                $attachment->encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE;
                $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                $attachment->filename = 'event.ics';
                
                $attachments = array($attachment);
            } else {
                $calendarPart = null;
                $attachments = null;
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " receiver: '{$_attender->getEmail()}'");
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " subject: '$messageSubject'");
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " body: $messageBody");
            
            // NOTE: this is a contact as we only support users and groupmembers
            $contact = $_attender->getResolvedUser();
            $sender = $_action == 'alarm' ? $organizer : $_updater;
        
            Tinebase_Notification::getInstance()->send($sender, array($contact), $messageSubject, $messageBody, $calendarPart, $attachments);
        } catch (Exception $e) {
            return;
        }
    }
 }
