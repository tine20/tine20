<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Poll Controller
 *
 * @package Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_Poll extends Tinebase_Controller_Record_Abstract implements
    Felamimail_Controller_MassMailingPluginInterface
{
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = Calendar_Model_Poll::class;

    /**
     * do right checks - can be enabled/disabled by doRightChecks
     *
     * @var boolean
     */
    protected $_doRightChecks = false;

    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = false;

    /**
     * use notes - can be enabled/disabled by useNotes
     *
     * @var boolean
     */
    protected $_setNotes = false;

    /**
     * Do we update relation to this record
     *
     * @var boolean
     */
    protected $_doRelationUpdate = false;

    /**
     * @var array (direct) fields to keep in sync across alternatives of same poll
     */
    protected $_syncFields = ['transp', 'class', 'description', 'geo', 'location', 'organizer', 'priority', 'status',
        'summary', 'url', 'is_all_day_event', 'originator_tz', 'mute', 'customfields', 'poll_id', 'container_id'];

    /**
     * @var string id of poll currently being inspected
     */
    protected $_inspectedPoll = null;

    /**
     * @var array contains polls cached e. g. during prepareMassMailingMessage
     */
    protected $_cachedPolls = [];

    /**
     * @var array contains cached attenders for pollIds e. g. during prepareMassMailingMessage
     */
    protected $_cachedAttenders = [];

    /**
     * @var Calendar_Controller_Poll
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Poll';

        $this->_backend         = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'cal_polls'
        ));
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
     * @return Calendar_Controller_Poll
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param string $pollId
     * @return Tinebase_Record_RecordSet
     */
    public function getPollEvents($pollId)
    {
        $poll = $this->get($pollId);
        $deletedEvents = $poll->deleted_events;

        $alternativeEvents = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'poll_id', 'operator' => 'equals', 'value' => $pollId],
            ['field' => 'is_deleted', 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
        ]));

        return is_array($deletedEvents) ? $alternativeEvents->filter(function(Calendar_Model_Event $event) use ($deletedEvents) {
            return ! in_array($event->getId(), $deletedEvents);
        }) : $alternativeEvents;
    }

    /**
     * @param Calendar_Model_Event $event
     * @return Calendar_Model_Event
     */
    public function setDefiniteEvent(Calendar_Model_Event $event)
    {
        $pollId = $event->poll_id instanceof Calendar_Model_Poll ? $event->poll_id->getId() : $event->poll_id;
        $poll = $this->get($pollId);

        try {
            $this->_inspectedPoll = $pollId;

            $existingAlternatives = $this->getPollEvents($pollId);
            $existingAlternatives->removeById($event->getId());

            $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(
                !Calendar_Config::getInstance()->get(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS));
            Calendar_Controller_Event::getInstance()->delete($existingAlternatives->getId());
            Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);

            $poll->closed = true;
            $this->update($poll);

            $event->poll_id = $pollId;
            $event->status = Calendar_Model_Event::STATUS_CONFIRMED;
            $updatedEvent = Calendar_Controller_Event::getInstance()->update($event);

        } finally {
            $this->_inspectedPoll = null;
        }

        return $updatedEvent;
    }

    /**
     * inspect event before it gets persistently created
     *
     * @param Calendar_Model_Event $event
     */
    public function inspectBeforeCreateEvent($event)
    {
        $this->_inspectEvent($event);
    }

    /**
     * inspect event before it gets persistently updated
     *
     * @param Calendar_Model_Event $event
     * @param Calendar_Model_Event $oldEvent
     */
    public function inspectBeforeUpdateEvent($event, $oldEvent)
    {
        $pollId = $event->poll_id instanceof Calendar_Model_Poll ? $event->poll_id->getId() : $event->poll_id;
        $oldPollId = $oldEvent->poll_id instanceof Calendar_Model_Poll ? $oldEvent->poll_id->getId() : $oldEvent->poll_id;

        if (!$pollId && !$oldPollId) {
            // nothing to do :-)
        } else if ($pollId && !$oldPollId) {
            // create/update poll. can an event be assigned to an existing poll?
            $this->_inspectEvent($event);
        } else if ($pollId && $pollId == $oldPollId) {
            // update poll
            $this->_inspectEvent($event);
        } else {
            throw new Tinebase_Exception_UnexpectedValue("Somthing wired happened");
        }
    }

    /**
     * inspect event before it gets persistently deleted
     *
     * @param  Tinebase_Record_RecordSet $events
     */
    public function inspectDeleteEvents($events)
    {
        $groupedEvents = [];
        foreach($events as $event) {
            $pollId = $event->poll_id instanceof Calendar_Model_Poll ? $event->poll_id->getId() : $event->poll_id;
            if ($pollId) {
                $groupedEvents[$pollId][] = $event->getId();
            }
        }

        foreach($groupedEvents as $pollId => $deletedEventIds) {
            if ($pollId != $this->_inspectedPoll) {
                $poll = $this->get($pollId);
                $poll->deleted_events = array_unique(array_merge($poll->deleted_events, $deletedEventIds));
                $this->update($poll);
            }
        }
    }

    /**
     * inspect event helper
     *
     * @param Calendar_Model_Event $event
     * @throws Tasks_Exception_UnexpectedValue
     */
    protected function _inspectEvent($event)
    {
        $poll = $event->poll_id;
        if ($poll instanceof Calendar_Model_Poll) {
            if ($event->rrule || $event->isRecurException()) {
                throw new Tasks_Exception_UnexpectedValue('Polls for recurring events are not supported');
            }
            try {
                $this->_inspectedPoll = $poll->getId();
                try {
                    $existingPoll = $this->get($poll->getId());
                    if ($existingPoll->closed) {
                        return;
                    }
                } catch (Tinebase_Exception_NotFound $e) {
                    $this->create($poll);
                }
                $event->poll_id = $poll->getId();
                $event->mute = $event->mute || Calendar_Config::getInstance()->get(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS);
                if ($poll->closed != true) {
                    $event->status = Calendar_Model_Event::STATUS_TENTATIVE;
                }

                $existingAlternatives = $this->getPollEvents($poll->getId());
                $existingAlternatives->removeById($event->getId());

                // by copy
                if (!$poll->alternative_dates instanceof Tinebase_Record_RecordSet) {
                    $poll->alternative_dates = clone $existingAlternatives;
                    $poll->alternative_dates->addRecord($event);
                    // get rid of references from original event
                }

                $this->_mergeEventIntoAlternatives($event, $poll->alternative_dates);
                $diff = $existingAlternatives->diff($poll->alternative_dates);

                Calendar_Controller_Event::getInstance()->delete(array_diff($diff->removed->getId(),
                    [$event->getId()]));

                // event which is being updated is removed from alternatives list -> delete by this update
                if (!in_array($event->dtstart, $poll->alternative_dates->dtstart)) {
                    $event->is_deleted = true;
                    $event->deleted_time = Tinebase_DateTime::now();
                    $event->deleted_by = Tinebase_Core::getUser()->getId();
                    $diff->removed->addRecord($event);
                }

                $poll->deleted_events = array_unique(array_merge(is_array($poll->deleted_events) ? $poll->deleted_events : [],
                    $diff->removed->getId()));
                $poll->container_id = $event->container_id;
                $this->update($poll);

                // during inspection controller ist set to not notify :(
                $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(true);

                foreach ($diff->added as $toAdd) {
                    // skip inspected event
                    if ($event->dtstart == $toAdd->dtstart) {
                        continue;
                    }

                    Calendar_Controller_Event::getInstance()->create($toAdd);
                }

                foreach ($diff->modified as $eventDiff) {
                    $toUpdate = $poll->alternative_dates->getById($eventDiff->id);
                    Calendar_Controller_Event::getInstance()->update($toUpdate);
                }

                Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);

            } finally {
                $this->_inspectedPoll = null;
            }
        }
    }

    /**
     * merge event details into alternative events
     *
     * @param Calendar_Model_Event $event
     * @param Tinebase_Record_RecordSet $alternativeEvents
     */
    protected function _mergeEventIntoAlternatives(Calendar_Model_Event $event, Tinebase_Record_RecordSet $alternativeEvents)
    {
        // adopt event length
        $eventLength = $event->dtstart->diff($event->dtend);
        // relations
        $relations = null;
        if (isset($event->relations)) {
            $relations = [];
            if (!empty($event->relations)) {
                if (is_array($event->relations)) {
                    $tmp = $event->relations;
                } else {
                    $tmp = $event->relations->toArray();
                }
                foreach ($tmp as $relation) {
                    $relations[] = [
                        'related_id' => $relation['related_id'],
                        'related_model' => $relation['related_model'],
                        'related_degree' => $relation['related_degree'],
                        'related_backend' => $relation['related_backend'],
                        'type' => isset($relation['type']) ? $relation['type'] : null,
                    ];
                }
            }
        }
        // tags
        $tags = null;
        if (isset($event->tags)) {
            if (is_array($event->tags) || $event->tags instanceof Tinebase_Record_RecordSet) {
                $tags = $event->tags;
            } else {
                $tags = [];
            }
        }
        // alarms
        $alarms = null;
        if (isset($event->alarms)) {
            if ($event->alarms instanceof Tinebase_Record_RecordSet) {
                $alarms = clone $event->alarms;
            } else {
                $alarms = new Tinebase_Record_RecordSet(Tinebase_Model_Alarm::class, $event->alarms, true);
            }
            $alarms->id = null;
        }
        // attachements
        $attachments = null;
        if (isset($event->attachments)) {
            if ($event->attachments instanceof Tinebase_Record_RecordSet) {
                $attachments = clone $event->attachments;
            } else {
                $attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, $event->attachments, true);
            }
            $attachments->path = null;
        }
        // notes
        $notes = null;
        if (isset($event->notes)) {
            if ($event->notes instanceof Tinebase_Record_RecordSet) {
                $notes = clone $event->notes;
                $notes->id = null;
                $notes = $notes->toArray();
            } else {
                $notes = [];
                foreach ($event->notes as $note) {
                    if (is_array($note) && isset($note['id'])) {
                        unset($note['id']);
                    }
                    $notes[] = $note;
                }
            }
        }

        /** @var Calendar_Model_Event $alternativeEvent */
        foreach($alternativeEvents as $alternativeEvent) {
            // manage event length
            $alternativeEvent->dtend = $alternativeEvent->dtstart->getClone()->add($eventLength);

            // manage attendee
            $alternativeEvent->attendee = $alternativeEvent->attendee instanceof Tinebase_Record_RecordSet ?
                $alternativeEvent->attendee : new Tinebase_Record_RecordSet(Calendar_Model_Attender::class);
            $remainingEventAttendees = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class);
            foreach($event->attendee as $attendee) {
                $remainingEventAttendee = Calendar_Model_Attender::getAttendee($alternativeEvent->attendee, $attendee);
                if (!$remainingEventAttendee) {
                    $remainingEventAttendee = clone $attendee;
                    $remainingEventAttendee->setId(null);
                    $alternativeEvent->attendee->addRecord($remainingEventAttendee);

                }
                $remainingEventAttendees->addRecord($remainingEventAttendee);
            }

            foreach($alternativeEvent->attendee as $attendee) {
                if (! Calendar_Model_Attender::getAttendee($remainingEventAttendees, $attendee)) {
                    $alternativeEvent->attendee->removeRecord($attendee);
                }
            }

            $alternativeEvent->relations = $relations;
            $alternativeEvent->tags = $tags;
            if (null !== $alarms) {
                $alternativeEvent->alarms = clone $alarms;
                $alternativeEvent->alarms->record_id = $alternativeEvent->getId();
            } else {
                $alternativeEvent->alarms = null;
            }
            $alternativeEvent->attachments = $attachments;
            $alternativeEvent->notes = $notes;
        }

        // sync direct properties
        foreach($this->_syncFields as $fieldName) {
            $alternativeEvents->{$fieldName} = $event{$fieldName};
        }
    }

    /**
     * @param Felamimail_Model_Message $_message
     * @return null
     */
    public function prepareMassMailingMessage(Felamimail_Model_Message $_message)
    {
        if (!is_array($_message->to) || !isset($_message->to[0])) {
            throw new Tinebase_Exception_UnexpectedValue('bad message, no to[0] set');
        }
        $emailTo = $_message->to[0];
        if (strpos($_message->body, '/Calendar/view/poll/') === false) {
            // nothing to do for us
            return;
        }
        if (!preg_match('#/Calendar/view/poll/([^/]+)#', $_message->body, $matches)) {
            throw new Tinebase_Exception_UnexpectedValue('invalid poll url found in body: ' . $_message->body);
        }
        $pollId = $matches[1];
        $replaceUrl = $matches[0];

        /** @var Calendar_Model_Poll $poll */
        if (!isset($this->_cachedPolls[$pollId])) {
            $poll = $this->get($pollId);
            $poll->alternative_dates = $this->getPollEvents($pollId);
            $this->_cachedPolls[$pollId] = $poll;
        } else {
            $poll = $this->_cachedPolls[$pollId];
        }

        if (!isset($this->_cachedAttenders[$pollId])) {
            /** @var Calendar_Model_Event $event */
            if (null === ($event = $poll->alternative_dates->getFirstRecord())) {
                throw new Tinebase_Exception_UnexpectedValue('invalid poll ' . $pollId . ', no alternative_dates found');
            }

            $this->_cachedAttenders[$pollId] = [];
            /** @var Calendar_Model_Attender $attender */
            foreach ($event->attendee as $attender) {
                // TODO what about groups? is it legal to invite groups to polls anyway?
                foreach (array_filter(array_merge((array)$attender->getEmail(), $attender->getEmailsFromHistory()))
                        as $email) {
                    $this->_cachedAttenders[$pollId][$email] = $attender->status_authkey;
                }
            }
        }

        if (isset($this->_cachedAttenders[$pollId][$emailTo])) {
            $_message->body = str_replace($replaceUrl, $replaceUrl . $emailTo . '/'
                . $this->_cachedAttenders[$pollId][$emailTo], $_message->body);
        }

        return;
    }

    public function publicApiMainScreen($pollId, $user = null, $authKey = null)
    {
        $view = new Zend_View();
        $view->setScriptPath('Calendar/views');

        $baseUrl = Tinebase_Core::getUrl() . '/';

        $fileMap = Tinebase_Frontend_Http::getAssetsMap();
        $view->jsFiles = [$baseUrl . $fileMap['Calendar/js/pollClient/src/index.es6.js']['js']];

        if (TINE20_BUILDTYPE != 'RELEASE') {
            if (TINE20_BUILDTYPE == 'DEVELOPMENT') {
                $view->jsFiles[] = $baseUrl . 'webpack-dev-server.js';
            } else {
                $view->jsFiles[0] = preg_replace('/\.js$/', '.debug.js', $view->jsFiles[0]);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' publicApiMainScreen');

//        $this->Tinebase_Frontend_Http::_setMainscreenHeaders();

        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write($view->render('pollClient.php'));
        return $response;
    }

    public function publicApiGetPoll($pollId, $user = null, $authKey = null)
    {
        $oldValueACL = $this->doContainerACLChecks(false);
        $oldValueRights = $this->doRightChecks(false);

        try {
            $poll = $this->get($pollId);
            $alternative_dates = Calendar_Controller_Poll::getInstance()->getPollEvents($pollId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return new \Zend\Diactoros\Response('php://memory', 404);
        } finally {
            $this->doContainerACLChecks($oldValueACL);
            $this->doRightChecks($oldValueRights);
        }
        $alternative_dates->sort('dtstart');
        $event = $alternative_dates[0];
        $timezone = new DateTimeZone($event->originator_tz);
        $dateformat = Tinebase_Record_Abstract::ISO8601LONG;
        $event->setTimezone($timezone);
        $poll->setTimezone($timezone);
        $alternative_dates->setTimezone($timezone);

        // NOTE: this is a public API so we reduce data to a minimum
        $dates = [];
        foreach ($alternative_dates as $date) {
            $dates[] = [
                'id' => $date->id,
                'dtstart' => $date->dtstart->format($dateformat),
                'dtend' => $date->dtend->format($dateformat),
            ];
        }

        $attendee_status= [];
        foreach ($event->attendee as $attendee){
            $id = $attendee['user_type'] . '-' . $attendee['user_id'];
            $status = [];
            foreach ($alternative_dates as $date) {
                $date_attendee = Calendar_Model_Attender::getAttendee($date->attendee, $attendee);
                $status[] = [
                    'id' => $date->id,
                    'dtstart' => $date->dtstart->format($dateformat),
                    'status' => $date_attendee->status,
                    'status_authkey' => $date_attendee->status_authkey
                ];
            }
            $attendee_status[] = [
                'id' => $id,
                'name' => $attendee->getName(),
                'type' => $attendee['user_type'],
                'status' => $status,
            ];
        }

        $authorization = Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->get('Authorization');

        $authPassword = null;
        if ($authorization) {
            $authString = base64_decode(explode(' ', $authorization->getFieldValue())[1]);
            $authPassword = explode(':', $authString)[1];
        }

        if ($poll->password && $authPassword !== $poll->password) {
            return new \Zend\Diactoros\Response('php://memory', 401);
        }

        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write(json_encode(array_merge($poll->toArray(), [
            'dates' => $dates,
            'attendee_status' => $attendee_status,
            'config' => [
                'has_gtc' => !!Calendar_Config::getInstance()->get(Calendar_Config::POLL_AGB),
                'status_available' => Calendar_Config::getInstance()->get(Calendar_Config::ATTENDEE_STATUS)->toArray(),
                'current_contact' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId(), TRUE)->toArray()
            ]
        ])));
        return $response;
    }

    public function publicApiAddAttender($pollId)
    {
        // @TODO prohibit add for locked polls
        // @TODO do we need rate limiting here?
        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write('OK: ' . $pollId);
        return $response;
    }

    public function publicApiUpdateAttenderStatus($pollId)
    {
        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write('OK: ' . $pollId);
        return $response;
    }

    public function publicApiGetAGB()
    {
        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write(Calendar_Config::getInstance()->get(Calendar_Config::POLL_AGB));
        return $response;
    }
}