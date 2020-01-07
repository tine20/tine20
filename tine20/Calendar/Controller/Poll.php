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
            $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);

            $poll->closed = true;
            $updatedPoll = $this->update($poll);

            $event->poll_id = $pollId;
            $event->status = Calendar_Model_Event::STATUS_CONFIRMED;
            $updatedEvent = Calendar_Controller_Event::getInstance()->update($event);

            // @TODO: append/be iMIP invitation if $sendNotifications is false
            Tinebase_ActionQueue::getInstance()->queueAction(self::class . '.sendDefiniteEventNotifications',
                $updatedPoll,
                $updatedEvent
            );

        } finally {
            $this->_inspectedPoll = null;
            Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);
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
                $event->mute = $event->mute || Calendar_Config::getInstance()->get(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS);
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
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _inspectEvent($event)
    {
        $poll = $event->poll_id;
        if ($poll instanceof Calendar_Model_Poll) {
            if ($event->rrule || $event->isRecurException()) {
                // _('Polls for recurring events are not supported')
                throw new Tinebase_Exception_SystemGeneric('Polls for recurring events are not supported');
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

                    $toAdd->mute = $event->mute || Calendar_Config::getInstance()->get(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS);
                    Calendar_Controller_Event::getInstance()->create($toAdd);
                }

                foreach ($diff->modified as $eventDiff) {
                    $toUpdate = $poll->alternative_dates->getById($eventDiff->id);
                    $toUpdate->mute = $event->mute || Calendar_Config::getInstance()->get(Calendar_Config::POLL_MUTE_ALTERNATIVES_NOTIFICATIONS);
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
            $alternativeEvents->{$fieldName} = $event->{$fieldName};
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
        if (!preg_match('#/Calendar/view/poll/([^/^\s]+)#', $_message->body, $matches)) {
            throw new Tinebase_Exception_UnexpectedValue('invalid poll url found in body: ' . $_message->body);
        }
        $pollId = $matches[1];

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
                    $this->_cachedAttenders[$pollId][$email] = $attender;
                }
            }
        }

        if (isset($this->_cachedAttenders[$pollId][$emailTo])) {
            $attendee = $this->_cachedAttenders[$pollId][$emailTo];
            $_message->body = str_replace($poll->getPollLink(), $poll->getPollLink($attendee), $_message->body);
        }

        return;
    }

    public function publicApiMainScreen($pollId, $userKey = null, $authKey = null)
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=Calendar";
        $jsFiles[] = 'Calendar/js/pollClient/src/index.es6.js';

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles);
    }

    public function assertPublicUsage()
    {
        $currentUser = Tinebase_Core::getUser();
        if (! $currentUser) {
            Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()
                ->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS));
        }

        $oldvalues = [
            'containerACLChecks'    => $this->doContainerACLChecks(false),
            'rightChecks'           => $this->doRightChecks(false),
            'cceContainerACLChecks' => Calendar_Controller_Event::getInstance()->doContainerACLChecks(false),
            'cceRightChecks'        => Calendar_Controller_Event::getInstance()->doRightChecks(false),
            'cceSendNotifications'        => Calendar_Controller_Event::getInstance()->sendNotifications(false),
            'currentUser'           => $currentUser,
        ];


        return function() use ($oldvalues) {
            $this->doContainerACLChecks($oldvalues['containerACLChecks']);
            $this->doRightChecks($oldvalues['rightChecks']);
            Calendar_Controller_Event::getInstance()->doContainerACLChecks($oldvalues['cceContainerACLChecks']);
            Calendar_Controller_Event::getInstance()->doRightChecks($oldvalues['cceRightChecks']);
            Calendar_Controller_Event::getInstance()->sendNotifications($oldvalues['cceSendNotifications']);
            if ($oldvalues['currentUser']) {
                Tinebase_Core::set(Tinebase_Core::USER, $oldvalues['currentUser']);
            }
        };
    }

    protected function _checkAuth($poll)
    {
        $authorization = Tinebase_Core::get(Tinebase_Core::REQUEST)->getHeaders()->get('Authorization');

        $authPassword = null;
        if ($authorization) {
            $authString = base64_decode(explode(' ', $authorization->getFieldValue())[1]);
            $authPassword = explode(':', $authString)[1];
        }

        if ($poll->password && $authPassword !== $poll->password) {
            throw new Tinebase_Exception_Record_NotAllowed();
        }
    }

    public function publicApiGetPoll($pollId, $userKey = null, $authKey = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        $anonymousAccess = Tinebase_Core::getUser()->accountLoginName == 'anonymoususer';

        try {
            $poll = $this->get($pollId);
            $this->_checkAuth($poll);

            $alternative_dates = Calendar_Controller_Poll::getInstance()->getPollEvents($pollId);

            // check authkey
            // NOTE: maybe we have different authkeys for some reason, so lets check that at least one matches
            if ($anonymousAccess && $userKey) {
                $user = Calendar_Model_Attender::fromKey($userKey);
                $authKeys = [];
                foreach($alternative_dates as $date) {
                    $authKeys[$date->id] = Calendar_Model_Attender::getAttendee($date->attendee, $user)->status_authkey;
                }
                if (! in_array($authKey, $authKeys)) {
                    throw new Tinebase_Exception_Record_NotAllowed('authkey mismatch');
                }

            }

            // fill cache, cleanup status_authkeys
            Calendar_Model_Attender::resolveAttendee($alternative_dates->attendee, true, $alternative_dates);
            foreach($alternative_dates as $date) {
                $currentUserAttendee = $anonymousAccess && !$userKey ? null :
                    Calendar_Model_Attender::getAttendee($date->attendee, Calendar_Model_Attender::fromKey($anonymousAccess ? $userKey : ('user-' . Tinebase_Core::getUser()->contact_id)));

                foreach($date->attendee as $attendee) {
                    // flatten
                    $attendee['user_id'] = $attendee['user_id'] instanceof Tinebase_Record_Interface ? $attendee['user_id']->getId() : $attendee['user_id'];

                    // manage authkeys
                    if ($anonymousAccess) {
                        // for anonymous remove all authkeys but own
                        $attendee->status_authkey = $userKey == $attendee->getKey() ? $authKeys[$date->id] : null;
                    } else {
                        // if user is no attendee yet remove all authkeys
                        if (! $currentUserAttendee) {
                            $attendee->status_authkey = null;
                        }
                    }
                }
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
                $status = [];
                foreach ($alternative_dates as $date) {
                    $date_attendee = Calendar_Model_Attender::getAttendee($date->attendee, $attendee);
                    if ($date_attendee) {
                        $status[] = array_merge(array_intersect_key($date_attendee->toArray(), array_flip([
                            'id', 'cal_event_id', 'status', 'user_type', 'user_id', 'status_authkey'
                        ])), [
                            'info_url' => $anonymousAccess ? '' : ("/#/Calendar/pollFBView/{$attendee['user_type']}/{$attendee['user_id']}/" . $date->dtstart->format('Y-m-d')),
                        ]);
                    }
                }
                $attendee_status[] = [
                    'key'       => $attendee['user_type'] . '-' . $attendee['user_id'],
                    'user_id'   => $attendee['user_id'],
                    'user_type' => $attendee['user_type'],
                    'name'      => $attendee->getName(),
                    'status'    => $status,
                ];
            }

            usort($attendee_status, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            $response = new \Zend\Diactoros\Response();
            $response->getBody()->write(json_encode(array_merge($poll->toArray(), [
                'event_summary'     => $event->summary,
                'event_organizer'   => $event->resolveOrganizer()->n_fn,
                'alternative_dates' => $dates,
                'attendee_status'   => $attendee_status,
                'config' => [
                    'locale'            => (string) Tinebase_Core::getLocale(),
                    'has_gtc'           => !!Calendar_Config::getInstance()->get(Calendar_Config::POLL_GTC),
                    'status_available'  => Calendar_Config::getInstance()->get(Calendar_Config::ATTENDEE_STATUS)->toArray(),
                    'is_anonymous'      => Tinebase_Core::getUser()->accountLoginName == 'anonymoususer',
                    'current_contact'   => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId(), TRUE)->toArray(),
                    'jsonKey'           => Tinebase_Core::get('jsonKey'),
                    'brandingWeburl'    => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_WEBURL),
                    'brandingLogo'      => Tinebase_ImageHelper::getDataUrl(Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_LOGO)),
                    'installLogo'       => Tinebase_ImageHelper::getDataUrl(Tinebase_Core::getInstallLogo()),
                    'brandingTitle'     => Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE),
                ]
            ])));

        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Zend\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Zend\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }

        return $response;
    }

    public function publicApiAddAttendee($pollId)
    {
        // @TODO do we need rate limiting here? -> yes!
        $assertAclUsage = $this->assertPublicUsage();
        $anonymousAccess = Tinebase_Core::getUser()->accountLoginName == 'anonymoususer';

        try {
            $poll = $this->get($pollId);
            $this->_checkAuth($poll);

            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);

            // prohibit add for locked polls
            if ($poll->locked) {
                throw new Tinebase_Exception_Record_NotAllowed('poll is locked');
            }

            // prohibit add for closed polls
            if ($poll->closed) {
                throw new Tinebase_Exception_Record_NotAllowed('poll is closed');
            }

            // check if user has an account
            if ($anonymousAccess) {
                try {
                    $existingUser = Tinebase_User::getInstance()->getUserByProperty('accountEmailAddress',
                        $request['email']);
                    throw new Tinebase_Exception_Record_NotAllowed('user has account, please log in');
                } catch (Tinebase_Exception_NotFound $tenf) {
                }
            }


            // check if user has a contact
            // -- where is it added? anonymous user personal addressbook???
            $translation = Tinebase_Translation::getTranslation('Calendar');
            $contact = Calendar_Model_Attender::resolveEmailToContact($request, true, array_merge([
                'note' => $translation->_('This contact has been automatically added by the system as a poll attendee')
            ], Addressbook_Model_Contact::splitName($request['name'])));
            $user = new Calendar_Model_Attender([
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id' => $contact->getId(),
            ]);

            $returnAttendees = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class);
            foreach($request['status'] as $idx => $date) {
                $event = Calendar_Controller_Event::getInstance()->get($date['cal_event_id']);
                $attendee = clone $user;
                $attendee->status = $date['status'];

                // NOTE: as poll is not resolved here, poll inspection does not copy attendee to alternatives
                if ($existingAttendee = Calendar_Model_Attender::getAttendee($event->attendee, $user)) {
                    if (false /* some switch as the bellow effectively circumvents authkey */) {
                        throw new Tinebase_Exception_Record_NotAllowed('user is attendee, please use personal link');
                    }
                    $existingAttendee->status = $date['status'];
                    $returnAttendee = Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $existingAttendee, $existingAttendee->status_authkey);
                } else {
                    $event->attendee->addRecord($attendee);
                    $event = Calendar_Controller_Event::getInstance()->update($event);
                    $returnAttendee = Calendar_Model_Attender::getAttendee($event->attendee, $user);
                }
                $returnAttendees->addRecord($returnAttendee);
            }

            $this->_sendPollConfirmationMail($poll, $contact);

            // @TODO: queue some sort of notification for organizer?

            $response = new \Zend\Diactoros\Response();
            $response->getBody()->write(json_encode($returnAttendees->toArray()));

        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Zend\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Zend\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }

        return $response;
    }

    public function publicApiUpdateAttendeeStatus($pollId)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $poll = $this->get($pollId);
            $this->_checkAuth($poll);

            // prohibit add for closed polls
            if ($poll->closed) {
                throw new Tinebase_Exception_Record_NotAllowed('poll is closed');
            }

            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);

            foreach($request['status'] as $date) {
                $event = Calendar_Controller_Event::getInstance()->get($date['cal_event_id']);
                $attendee = new Calendar_Model_Attender($date);

                Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $attendee, $attendee->status_authkey);
            }

            // @TODO: queue some sort of notification

            $response = new \Zend\Diactoros\Response();
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Zend\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Zend\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }

        return $response;
    }

    // not ready - not yet used
    public function publicApiRequestPersonalLink($pollId)
    {
        // @TODO add rate limiting

        // allow for accounts and externals
        // they need to be part of the poll
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $poll = $this->get($pollId);
            $this->_checkAuth($poll);

            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);

            $contact = Calendar_Model_Attender::resolveEmailToContact($request, false);
            if (! $contact) {
                throw new Tinebase_Exception_NotFound();
            }

            $alternative_dates = Calendar_Controller_Poll::getInstance()->getPollEvents($pollId);
            $currentAttendee = Calendar_Model_Attender::getAttendee($alternative_dates->getFirstRecord()->attendee, new Calendar_Model_Attender([
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id' => $contact->getId(),
            ]));

            if (! $currentAttendee) {
                throw new Tinebase_Exception_NotFound();
            }

            // TODO what to send now? - we don't have a generic poll notification yet
//            Calendar_Controller_EventNotifications::getInstance()->sendNotificationToAttender($currentAttendee)

            $response = new \Zend\Diactoros\Response();
        } catch (Tinebase_Exception_NotFound $tenf) {
            return new \Zend\Diactoros\Response('php://memory', 404);
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            return new \Zend\Diactoros\Response('php://memory', 401);
        } finally {
            $assertAclUsage();
        }

        return $response;

    }

    public function publicApiGetAGB()
    {
        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write(Calendar_Config::getInstance()->get(Calendar_Config::POLL_GTC));
        return $response;
    }

    protected function _sendPollConfirmationMail(Calendar_Model_Poll $poll, Addressbook_Model_Contact $contact)
    {
        $alternativeEvents = $this->getPollEvents($poll->getId());
        $event = $alternativeEvents->getFirstRecord();
        $attendee = new Calendar_Model_Attender([
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id' => $contact,
        ]);
        $organiser = $event->resolveOrganizer();
        list($prefUser, $locale, $timezone, $translate, $sendLevel, $sendOnOwnActions, $sendAlarms) =
            Calendar_Controller_EventNotifications::getNotificationPreferences($attendee, $event);

        $twig = new Tinebase_Twig($locale, $translate);

        // TODO what about the html template?
        //$htmlTemplate = $twig->load('Calendar/views/pollConfirmationMail.html.twig');
        $textTemplate = $twig->load('Calendar/views/pollConfirmationMail.text.twig');

        $renderContext = [
            'name'              => $poll->name ? $poll->name : $event->summary,
            'recipient'         => $contact,
            'link'              => $poll->getPollLink($attendee),
            'organiser'         => $organiser,
            'poll'              => $poll,
            'event'             => $event,
            'alternativeEvents' => $alternativeEvents,
        ];

        $subject = sprintf($translate->_('Attendance Confirmation for Poll "%1$s"'), $renderContext['name']);

        Tinebase_Notification::getInstance()->send($prefUser, [$contact], $subject,
            $textTemplate->render($renderContext)/*, $htmlTemplate->render($renderContext)*/);
    }

    public function sendDefiniteEventNotifications(Calendar_Model_Poll $poll, Calendar_Model_Event $definiteEvent)
    {
        /** @var Addressbook_Model_Contact $organiser */
        $organiser = $definiteEvent->resolveOrganizer();

        /** @var Calendar_Model_Attender $attendee */
        foreach ($definiteEvent->attendee as $attendee) {
            list($prefUser, $locale, $timezone, $translate, $sendLevel, $sendOnOwnActions, $sendAlarms) =
                Calendar_Controller_EventNotifications::getNotificationPreferences($attendee, $definiteEvent);

            if ($attendee->user_type === Calendar_Model_Attender::USERTYPE_GROUP || $attendee->user_type === Calendar_Model_Attender::USERTYPE_LIST) {
                // list members are separate attendee - skip this
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Skipping group/list attender notification');
                continue;
            }

            /** @var Addressbook_Model_Contact $contact */
            $contact = $attendee->getResolvedUser();

            $twig = new Tinebase_Twig($locale, $translate);

            // TODO what about the html template?
            //$htmlTemplate = $twig->load('Calendar/views/pollDefiniteEventMail.html.twig');
            $textTemplate = $twig->load('Calendar/views/pollDefiniteEventMail.text.twig');

            $renderContext = [
                'name'              => $poll->name ? $poll->name : $definiteEvent->summary,
                'sstart'            => Tinebase_Translation::dateToStringInTzAndLocaleFormat($definiteEvent->dtstart, $timezone, $locale, 'datetime', true),
                'recipient'         => $contact,
                'link'              => $poll->getPollLink($attendee),
                'organiser'         => $organiser,
                'poll'              => $poll,
                'event'             => $definiteEvent,
            ];

            $subject = sprintf($translate->_('%1$s is scheduled for %2$s'), $renderContext['name'], $renderContext['sstart']);

            Tinebase_Notification::getInstance()->send($prefUser, [$contact], $subject,
                $textTemplate->render($renderContext)/*, $htmlTemplate->render($renderContext)*/);
        }
    }
}
