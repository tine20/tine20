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
            case 'created':
            case 'deleted':
                foreach($_event->attendee as $attender) {
                    $this->sendNotificationToAttender($attender, $_event, $_updater, $_action);
                }
                break;
            case 'changed':
                $attendeeMigration = $_oldEvent->attendee->getMigration($_event->attendee->getArrayOfIds());
                
                foreach ($attendeeMigration['toCreateIds'] as $attenderId) {
                    $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
                    $this->sendNotificationToAttender($attender, $_event, $_updater, 'created');
                }
                
                foreach ($attendeeMigration['toDeleteIds'] as $attenderId) {
                    $attender = $_oldEvent->attendee[$_oldEvent->attendee->getIndexById($attenderId)];
                    $this->sendNotificationToAttender($attender, $_oldEvent, $_updater, 'deleted');
                }
                
                if (! empty($attendeeMigration['toUpdateIds'])) {
                    $updates = $_event->diff($_oldEvent);
                    
                    foreach ($attendeeMigration['toUpdateIds'] as $attenderId) {
                        $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
                        $this->sendNotificationToAttender($attender, $_event, $_updater, 'changed', $updates);
                    }
                }
                
                break;
                
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
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
     * @param array                      $_updates
     * @return void
     */
    public function sendNotificationToAttender($_attender, $_event, $_updater, $_action, $_updates=NULL)
    {
        if (! in_array($_attender->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
            // don't send notifications to non persons
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
        
        // get prefered language and timezone
        $prefUser = $_attender->getUserAccountId();
        if (! $prefUser) {
            $prefUser = $organizer;
        }
        $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $prefUser));
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $prefUser);
        $translate = Tinebase_Translation::getTranslation('Calendar', $locale);

        // get date strings
        $startDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtstart, $timezone, $locale);
        $endDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtend, $timezone, $locale);
        
        switch ($_action) {
            case 'alarm':
                $messageSubject = sprintf($translate->_('Alarm for event "%s" at %s'), $_event->summary, $startDateString);
                $messageBody = $translate->_('Here is your requested alarm for to following event:') . "\n\n";
                break;
            case 'created':
                $messageSubject = sprintf($translate->_('Event invitation "%s" at %s'), $_event->summary, $startDateString);
                $messageBody = $translate->_('You have been invited to the following event:') . "\n\n";
                break;
            case 'deleted':
                $messageSubject = sprintf($translate->_('Event "%s" at %s has been canceled' ), $_event->summary, $startDateString);
                $messageBody = $translate->_('The following event has been canceled:') . "\n\n";
                break;
            case 'changed':
                if (count(array_intersect(array('dtstart', 'dtend'), array_keys($_updates))) > 0) {
                    $messageSubject = sprintf($translate->_('Event "%s" at %s has been rescheduled' ), $_event->summary, $startDateString);
                    $messageBody  = $translate->_('The following event has been rescheduled:') . "\n";
                    $messageBody .= $translate->_('From') . ': ' . 
                        (array_key_exists('dtstart', $_updates) ? Tinebase_Translation::dateToStringInTzAndLocaleFormat($_updates['dtstart'], $timezone, $locale) : $startDateString) . " - " .
                        (array_key_exists('dtstart', $_updates) ? Tinebase_Translation::dateToStringInTzAndLocaleFormat($_updates['dtend'], $timezone, $locale) : $endDateString) . "\n";
                    $messageBody .= $translate->_('To') . ': ' . $startDateString . ' - ' . $endDateString . "\n\n";
                } else {
                    $messageSubject = sprintf($translate->_('Event "%s" at %s has been updated' ), $_event->summary, $startDateString);
                    $messageBody = $translate->_('The following event has been updated:') . "\n\n";
                }
                break;
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
        }
        
        // add values to text
        $messageBody .= $_event->summary . "\n\n" 
            . $translate->_('Start')        . ': ' . $startDateString   . "\n" 
            . $translate->_('End')          . ': ' . $endDateString     . "\n"
            //. $translate->_('Organizer')    . ': ' . $_event->organizer   . "\n" 
            . $translate->_('Location')     . ': ' . $_event->location    . "\n"
            . $translate->_('Description')  . ': ' . $_event->description . "\n\n"
            
            . $translate->plural('Attender', 'Attendee', count($_event->attendee)). ":\n";
        
        foreach ($_event->attendee as $attender) {
            $status = $translate->translate($attender->getStatusString());
            
            $messageBody .= "{$attender->getName()} ($status) \n";
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " receiver: '{$_attender->getEmail()}'");
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " subject: '$messageSubject'");
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " body: $messageBody");
        
        // NOTE: this is a contact as we only support users and groupmembers
        $contact = $_attender->getResolvedUser();
        Tinebase_Notification::getInstance()->send($organizer, array($contact), $messageSubject, $messageBody);
    }
 }