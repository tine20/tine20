<?php
/**
 * Calendar Event Notifications
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
     
     const INVITATION_ATTACHMENT_MAX_FILESIZE           = 2097152; // 2 MB
     
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
        $diff = $_event->diff($_oldEvent)->diff;
        
        $orderedUpdateFieldOfInterest = array(
            'dtstart', 'dtend', 'rrule', 'summary', 'location', 'description',
            'transp', 'priority', 'status', 'class',
            'url', 'is_all_day_event', 'originator_tz', /*'tags', 'notes',*/
        );
        
        $updates = array();
        foreach ($orderedUpdateFieldOfInterest as $field) {
            if ((isset($diff[$field]) || array_key_exists($field, $diff))) {
                $updates[$field] = $diff[$field];
            }
        }
        
        // rrule legacy
        if ((isset($updates['rrule']) || array_key_exists('rrule', $updates))) {
            $updates['rrule'] = $_oldEvent->rrule;
        }
        
        // check for organizer update
        if (Tinebase_Record_Abstract::convertId($_event['organizer'], 'Addressbook_Model_Contact') != 
            Tinebase_Record_Abstract::convertId($_oldEvent['organizer'], 'Addressbook_Model_Contact')) {
            
            $updates['organizer'] = $_event->resolveOrganizer();
        }
        
        // check attendee updates
        $attendeeMigration = Calendar_Model_Attender::getMigration($_oldEvent->attendee, $_event->attendee);
        foreach ($attendeeMigration['toUpdate'] as $attendee) {
            $oldAttendee = Calendar_Model_Attender::getAttendee($_oldEvent->attendee, $attendee);
            if ($oldAttendee && $attendee->status == $oldAttendee->status) {
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
     * @param Tinebase_Model_FullUser    $_updater
     * @param String                     $_action
     * @param Tinebase_Record_Interface  $_oldEvent
     * @param Array                      $_additionalData
     * @throws Calendar_Exception
     *
     * @refactor split up this function, it's way too long
     */
    public function doSendNotifications(Calendar_Model_Event $_event, $_updater, $_action, Tinebase_Record_Interface $_oldEvent = NULL, array $_additionalData = array())
    {
        if (isset($_additionalData['alarm']))
        {
            $_alarm = $_additionalData['alarm'];
        } else {
            $_alarm = null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " send notifications for event: ". print_r($_event->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && $_oldEvent) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " old event: ". print_r($_oldEvent->toArray(), TRUE));

        // we only send notifications to attendee
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet && 'tentative' !== $_action) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Event has no attendee");
            return;
        }

        if ($_event->dtend === NULL) {
            throw new Tinebase_Exception_UnexpectedValue('no dtend set in event');
        }
        
        if (Tinebase_DateTime::now()->subHour(1)->isLater($_event->dtend)) {
            if ($_action == 'alarm' || ! ($_event->isRecurException() || $_event->rrule)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . " Skip notifications to past events");
                return;
            }
        }
        
        $notificationPeriodConfig = Calendar_Config::getInstance()->get(Calendar_Config::MAX_NOTIFICATION_PERIOD_FROM);
        if (Tinebase_DateTime::now()->subWeek($notificationPeriodConfig)->isLater($_event->dtend)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Skip notifications to past events (MAX_NOTIFICATION_PERIOD_FROM: " . $notificationPeriodConfig . " week(s))");
            return;
        }
        
        // lets resolve attendee once as batch to fill cache
        if (null !== $_event->attendee) {
            $attendee = clone $_event->attendee;
            Calendar_Model_Attender::resolveAttendee($attendee);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Notification action: " . $_action);


        $organizerContact = $_event->resolveOrganizer();
        if (! $organizerContact) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Organizer missing - using creator as organizer for notification purposes.');
            $organizerContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_event->created_by);
        }

        $organizerIsAttender = false;
        foreach ($_event->attendee as $attender) {
            if ($attender->getUserId() === $organizerContact->getId()) {
                $organizerIsAttender = true;
            }
        }

        $organizerIsExternal = ! $organizerContact->account_id;

        $organizer = new Calendar_Model_Attender(array(
            'user_type'  => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'    => $organizerContact
        ));

        switch ($_action) {
            case 'alarm':
                foreach($_event->attendee as $attender) {
                    if (Calendar_Model_Attender::isAlarmForAttendee($attender, $_alarm)) {
                        $this->sendNotificationToAttender($attender, $_event, $_updater, $_action, self::NOTIFICATION_LEVEL_NONE);
                    }
                }
                break;
            case 'booked':
            case 'created':
            case 'deleted':
                // skip invitations/cancle if event came from external
                if (! $organizerIsExternal) {
                    foreach ($_event->attendee as $attender) {
                        $this->sendNotificationToAttender($attender, $_event, $_updater, $_action, self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                    }
                } else {
                    // send reply (aka status update) to external organizer
                    $this->sendNotificationToAttender($organizer, $_event, $_updater, 'changed', self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE, [
                        'attendee' => [
                            'toUpdate' => new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [Calendar_Model_Attender::getOwnAttender($_event->attendee)])
                        ]
                    ]);
                }
                break;
            case 'changed':
                if (! $organizerIsExternal) {
                    if (! $_oldEvent) {
                        throw new Calendar_Exception('missing oldEvent ... can not get attendee migration');
                    }

                    $attendeeMigration = Calendar_Model_Attender::getMigration($_oldEvent->attendee, $_event->attendee);
                    foreach ($attendeeMigration['toCreate'] as $attender) {
                        $this->sendNotificationToAttender($attender, $_event, $_updater, 'created', self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                    }

                    foreach ($attendeeMigration['toDelete'] as $attender) {
                        $this->sendNotificationToAttender($attender, $_oldEvent, $_updater, 'deleted', self::NOTIFICATION_LEVEL_INVITE_CANCEL);
                    }

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

                    // NOTE: toUpdate are all attendee to be notified
                    if (count($attendeeMigration['toUpdate']) > 0) {
                        // send notifications
                        foreach ($attendeeMigration['toUpdate'] as $attender) {
                            $this->sendNotificationToAttender($attender, $_event, $_updater, 'changed', $notificationLevel, $updates);
                        }
                    }

                    if (! $organizerIsAttender) {
                        $this->sendNotificationToAttender($organizer, $_event, $_updater, 'changed', $notificationLevel, $updates);
                    }
                } else {
                    // NOTE: a reply to an external reschedule is a reschedule for us, but a status update only for external!
                    $this->sendNotificationToAttender($organizer, $_event, $_updater, 'changed', self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE, [
                        'attendee' => [
                            'toUpdate' => new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [Calendar_Model_Attender::getOwnAttender($_event->attendee)])
                        ]
                    ]);
                }
                break;

            case 'tentative':
                
                $prefUser = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::SEND_NOTIFICATION_FOR_TENTATIVE,
                    $organizerContact->account_id);
                $attendee = new Calendar_Model_Attender(array(
                    'cal_event_id'      => $_event->getId(),
                    'user_type'         => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'           => $_event->organizer,
                ), true);
                if($prefUser) {
                    $this->sendNotificationToAttender($attendee, $_event, $_updater, 'tentative', self::NOTIFICATION_LEVEL_NONE);
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
     * @param Tinebase_Model_FullUser    $_updater
     * @param string                     $_action
     * @param string                     $_notificationLevel
     * @param array                      $_updates
     * @return void
     *
     * @throws Exception
     *
     * TODO this needs major refactoring
     */
    public function sendNotificationToAttender(Calendar_Model_Attender $_attender, $_event, $_updater, $_action, $_notificationLevel, $_updates = NULL)
    {
        try {
            $organizer = $_event->resolveOrganizer();
            $attendee = $_attender->getResolvedUser();

            if ($attendee instanceof Addressbook_Model_List) {
                // don't send notification to lists as we already resolved the list members for individual mails
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Skip notification for list " . $attendee->name);
                return;
            }
            if (!$attendee instanceof Tinebase_Record_Interface) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . " Skip notification for unknown attende: " . print_r($attendee, true) . ' attender: ' . print_r($_attender, true));
                return;
            }

            list($prefUser, $locale, $timezone, $translate, $sendLevel, $sendOnOwnActions, $sendAlarms) = self::getNotificationPreferences($_attender, $_event);
            $attendeeAccountId = $_attender->getUserAccountId();

            $recipients = array($attendee);

            $this->_handleResourceEditors($_attender, $_notificationLevel, $recipients, $_action, $sendLevel, $_updates);

            // check if user wants this notification NOTE: organizer gets mails unless she set notificationlevel to NONE
            // NOTE prefUser is organizer for external notifications
            if ((null !== $_updater && $attendeeAccountId == $_updater->getId() && ! $sendOnOwnActions && $_action !== 'alarm')
                || ($sendLevel < $_notificationLevel && (
                        ((is_object($organizer) && method_exists($attendee, 'getPreferredEmailAddress') && $attendee->getPreferredEmailAddress() != $organizer->getPreferredEmailAddress())
                        || (is_object($organizer) && !method_exists($attendee, 'getPreferredEmailAddress') && $attendee->email != $organizer->getPreferredEmailAddress()))
                        || $sendLevel == self::NOTIFICATION_LEVEL_NONE)
                   )
                ) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Preferred notification level not reached -> skipping notification for {$_attender->getEmail()}");
                return;
            }

            if ($_action == 'alarm' && ! $sendAlarms) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " User does not want alarm mails -> skipping notification for {$_attender->getEmail()}");
                return;
            }

            $method = NULL; // NOTE $method gets set in _getSubject as referenced param
            $messageSubject = $this->_getSubject($_event, $_notificationLevel, $_action, $_updates, $timezone, $locale, $translate, $method, $_attender);

            // we don't send iMIP parts to external attendee if config is active
            if (Calendar_Config::getInstance()->get(Calendar_Config::DISABLE_EXTERNAL_IMIP) && ! $attendeeAccountId) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " External iMIP is disabled.");
                $method = NULL;
            }

            $view = new Zend_View();
            $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');
            
            $view->translate    = $translate;
            $view->timezone     = $timezone;
            
            $view->event        = $_event;
            $view->updater      = $_updater;
            $view->updates      = $_updates;

            $view->attendeeAccountId = $attendeeAccountId;
            
            $messageBody = $view->render('eventNotification.php');
            
            $calendarPart = null;
            $attachments = $this->_getAttachments($method, $_event, $_action, $_updater, $calendarPart);
            
            $sender = $_action == 'alarm' ? $prefUser : $_updater;
            if (!empty($recipients)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " receiver: " . count($recipients));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " subject: '$messageSubject'");
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " body: $messageBody");
                
                Tinebase_Notification::getInstance()->send($sender, $recipients, $messageSubject, $messageBody, $calendarPart, $attachments);
            }
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Failed to send notification: ' . $e->getMessage());
            if ($_action === 'alarm') {
                // throw exception in case of alarm as the exception is catched in \Tinebase_Alarm::sendPendingAlarms
                // and alarm sending is marked as failure
                throw $e;
            }
            return;
        }
    }

     /**
      * @param Calendar_Model_Attender $attendee
      * @param Calendar_Model_Event $event
      * @return array
      */
    public static function getNotificationPreferences(Calendar_Model_Attender $attendee, Calendar_Model_Event $event)
    {
        $attendeeAccountId = $attendee->getUserAccountId();
        $organizer = $event->resolveOrganizer();
        $organizerAccountId = ($organizer instanceof Addressbook_Model_Contact) ? $organizer->account_id : null;

        $prefUserId = $attendeeAccountId ? $attendeeAccountId :
            ($organizerAccountId ? $organizerAccountId :
                ($event->created_by));

        try {
            $prefUser = Tinebase_User::getInstance()->getFullUserById($prefUserId);
        } catch (Exception $e) {
            $prefUser = Tinebase_Core::getUser();
            $prefUserId = $prefUser->getId();
        }

        // get prefered language, timezone and notification level

        $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $prefUserId));
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $prefUserId);
        $translate = Tinebase_Translation::getTranslation('Calendar', $locale);
        $sendLevel        = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::NOTIFICATION_LEVEL, $prefUserId);
        $sendOnOwnActions = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS, $prefUserId);
        $sendAlarms = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::SEND_ALARM_NOTIFICATIONS, $prefUserId);

        // external (non account) notification
        if (! $attendeeAccountId) {
            // external organizer needs status updates
            $sendLevel = is_object($organizer) && $attendee->getEmail() == $organizer->getPreferredEmailAddress() ? 40 : 30;
            $sendOnOwnActions = false;
            $sendAlarms = false;
        }

        return [$prefUser, $locale, $timezone, $translate, $sendLevel, $sendOnOwnActions, $sendAlarms];
    }

     /**
      * Not a resource? = Don't do anything
      * Suppress Notifications = Don't send anything. Neither to Users or Resource
      * ResourceMailsForEditors = Send to Editors and Resource
      * ! ResourceMailsForEditors = Send only to Resource
      *
      * @param $attender
      * @param $_notificationLevel
      * @param $recipients
      * @param $action
      * @param $sendLevel
      * @return bool
      */
     protected function _handleResourceEditors($attender, $_notificationLevel, &$recipients, &$action, &$sendLevel, $_updates)
     {
         // Add additional recipients for resources
         if ($attender->user_type !== Calendar_Model_Attender::USERTYPE_RESOURCE) {
             return true;
         }

         $resource = Calendar_Controller_Resource::getInstance()->get($attender->user_id);
         // Suppress all notifications?
         if ($resource->suppress_notification) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                 . " Do not send Notifications for this resource: ". $resource->name);
             // $recipients will still contain the resource itself
             // Edit 13.12.2016 Remove resource as well and supress ALL notifications
             $recipients = array();
             return true;
         }

         // Send Mails to Editors?
         if (! Calendar_Config::getInstance()->get(Calendar_Config::RESOURCE_MAIL_FOR_EDITORS)) {
             return true;
         }

         // Set custom status booked
         if ($action == 'created') {
             $action = 'booked';
         }

         // The resource has no account there for the organizer preference (sendLevel) is used. We don't want that
         $sendLevel = self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE;
         //handle attendee status change
         if(! empty($_updates['attendee']) && ! empty($_updates['attendee']['toUpdate'])) {
             foreach ($_updates['attendee']['toUpdate'] as $updatedAttendee) {
                 if ($updatedAttendee->user_type == Calendar_Model_Attender::USERTYPE_RESOURCE && $resource->getId() == $updatedAttendee->user_id) {
                     $sendLevel = self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE;
                 }
             }
         }
         
         /*
         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                 . " Attender: ". $attender);
         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                 . " Action: ". $action);
         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                 . " Notification Level: ". $_notificationLevel);
         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                 . " Send Level: ". $sendLevel);
         */
         
         $recipients = array_merge($recipients,
             Calendar_Controller_Resource::getInstance()->getNotificationRecipients($resource)
         );
     }
    
    /**
     * get notification subject and method
     * 
     * @param Calendar_Model_Event $_event
     * @param string $_notificationLevel
     * @param string $_action
     * @param array $_updates
     * @param string $timezone
     * @param Zend_Locale $locale
     * @param Zend_Translate $translate
     * @param string $method
     * @param Calendar_Model_Attender
     * @return string
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _getSubject($_event, $_notificationLevel, $_action, $_updates, $timezone, $locale, $translate, &$method, Calendar_Model_Attender $attender)
    {
        $startDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtstart, $timezone, $locale);

        switch ($_action) {
            case 'alarm':
                $messageSubject = sprintf($translate->_('Alarm for event "%1$s" at %2$s'), $_event->summary, $startDateString);
                break;
            case 'created':
                $messageSubject = sprintf($translate->_('Event invitation "%1$s" at %2$s'), $_event->summary, $startDateString);
                $method = Calendar_Model_iMIP::METHOD_REQUEST;
                break;
            case 'booked':
                if ($attender->user_type !== Calendar_Model_Attender::USERTYPE_RESOURCE) {
                    throw new Tinebase_Exception_UnexpectedValue('not a resource');
                }
                $resource = Calendar_Controller_Resource::getInstance()->get($attender->user_id);
                $messageSubject = sprintf(
                    $translate->_('Resource "%1$s" was booked for "%2$s" at %3$s'),
                    $resource->name,
                    $_event->summary,
                    $startDateString
                );
                $method = Calendar_Model_iMIP::METHOD_REQUEST;
                break;
            case 'deleted':
                $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been canceled' ), $_event->summary, $startDateString);
                $method = Calendar_Model_iMIP::METHOD_CANCEL;
                break;
            case 'changed':
                switch ($_notificationLevel) {
                    case self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE:
                        if ((isset($_updates['dtstart']) || array_key_exists('dtstart', $_updates))) {
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

            case 'tentative':
                $messageSubject = sprintf($translate->_('Tentative event notification for event "%1$s" at %2$s' ), $_event->summary, $startDateString);
                break;

            default:
                $messageSubject = 'unknown action';
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
        }
        if ($attender->user_type === Calendar_Model_Attender::USERTYPE_RESOURCE) {
            $messageSubject = '[' . $translate->_('Resource Management') . '] ' . $messageSubject;
        }
        return $messageSubject;
    }
    
    /**
     * get notification attachments
     * 
     * @param string $method
     * @param Calendar_Model_Event $event
     * @param string $_action
     * @param Tinebase_Model_FullUser $updater
     * @param Zend_Mime_Part $calendarPart
     * @return array
     */
    protected function _getAttachments($method, $event, $_action, $updater, &$calendarPart)
    {
        if ($method === NULL) {
            return array();
        }
        
        $vcalendar = $this->_createVCalendar($event, $method, $updater);
        
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
        
        // add other attachments (only on invitation)
        if ($_action == 'created' || $_action == 'booked') {
            $eventAttachments = $this->_getEventAttachments($event);
            $attachments = array_merge($attachments, $eventAttachments);
        }
        
        return $attachments;
    }
    
    /**
     * create iMIP VCALENDAR
     * 
     * @param Calendar_Model_Event $event
     * @param string $method
     * @param Tinebase_Model_FullAccount $updater
     * @return Sabre\VObject\Component
     */
    protected function _createVCalendar($event, $method, $updater)
    {
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $converter->setMethod($method);
        $vcalendar = $converter->fromTine20Model($event);
        
        foreach ($vcalendar->children() as $component) {
            if ($component->name == 'VEVENT') {
                if ($method != Calendar_Model_iMIP::METHOD_REPLY && $event->organizer !== $updater->contact_id) {
                    if (isset($component->{'ORGANIZER'})) {
                        // in Tine 2.0 non organizers might be given the grant to update events
                        // @see rfc6047 section 2.2.1 & rfc5545 section 3.2.18
                        $component->{'ORGANIZER'}->add('SENT-BY', 'mailto:' . $updater->accountEmailAddress);
                    }
                } else if ($method == Calendar_Model_iMIP::METHOD_REPLY) {
                    // TODO in Tine 2.0 status updater might not be updater
                    $component->{'REQUEST-STATUS'} = '2.0;Success';
                }
            }
        }
        
        return $vcalendar;
    }
    
    /**
     * get event attachments
     * 
     * @param Calendar_Model_Event $_event
     * @return array of Zend_Mime_Part
     */
    protected function _getEventAttachments($_event)
    {
        $attachments = array();
        foreach ($_event->attachments as $attachment) {
            if ($attachment->size < self::INVITATION_ATTACHMENT_MAX_FILESIZE) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Adding attachment " . $attachment->name . ' to invitation mail');
                
                $path = Tinebase_Model_Tree_Node_Path::STREAMWRAPPERPREFIX
                    . Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($_event)
                    . '/' . $attachment->name;
                
                $handle = fopen($path, 'r');
                $stream = fopen("php://temp", 'r+');
                stream_copy_to_stream($handle, $stream);
                rewind($stream);

                $part              = new Zend_Mime_Part($stream);
                $part->encoding    = Zend_Mime::ENCODING_BASE64; // ?
                $part->filename    = $attachment->name;
                $part->setTypeAndDispositionForAttachment($attachment->contenttype, $attachment->name);
                
                fclose($handle);
                
                $attachments[] = $part;
                
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Not adding attachment " . $attachment->name . ' to invitation mail (size: ' . Tinebase_Helper::convertToMegabytes($attachment-size) . ')');
            }
        }
        
        return $attachments;
    }
 }
