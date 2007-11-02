<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Lists.php 121 2007-09-24 19:42:55Z lkneschke $
 *
 */

/**
 * the classes needed to access the calendar related tables
 *
 * @see Calendar_Backend_Sql_Events, Calendar_Backend_Sql_Dates, Calendar_Backend_Sql_Extra, Calendar_Backend_Sql_Repeats, Calendar_Backend_Sql_User,
 */
require_once 'Calendar/Backend/Sql/Events.php';
require_once 'Calendar/Backend/Sql/Dates.php';
require_once 'Calendar/Backend/Sql/Extra.php';
require_once 'Calendar/Backend/Sql/Repeats.php';
require_once 'Calendar/Backend/Sql/User.php';

/**
 * The calendars SQL Backend is to complicated to just work on the standart
 * Zend_DB_Table and Zend_DB_TableRow classes.
 * 
 * Each eGroupWare installation has a calendar property called horizont. This
 * is the time, up to all repitions of recouring events are explicitly stored in
 * the SQL DB. This horinzont is pushed to later times, if _one_ user of the
 * installation looks at his calendar for these later times. However, there is
 * a maximum horizont (defaults 2 years). Queris for later times as this horizont
 * do not contain the repitions of recouring events!
 * 
 * Attension needs to be payed to recuring events. For a single repition of such 
 * an revent, the id is extended by the repition date: <id>-<repitition>.
 * Anytime an id like this comes here, this backend interprets it as the single
 * evnet out of an events series. If just the id w.o. an repitions extension is
 * given, this class acts on the whole series!
 * 
 * Eeach Calendar entry has has a id and a uid. The uid is there to have valid
 * (global) ids for ical access. For not recuring events the uid is just the
 * normal id with some global identifier added. In case of recuring events 
 * however this is not the case. For recuring events this backend extends the
 * normal ids by the repition date: <id>-<repitition>. But the uid's for all 
 * repitions are the same!. The reason for this differnce is the fact, that the 
 * ical standart does not support different participants and participants stati
 * for different recurences of the same event, whereas this backend is capable
 * to handle this.
 */
class Calendar_Backend_Sql implements Calendar_Backend_Interface
{
    /**
     * recuring event types
     */
    const RECUR_NONE         = 0;
    const RECUR_DAILY        = 1;
    const RECUR_WEEKLY       = 2;
    const RECUR_MONTHLY_MDAY = 3;
    const RECUR_MONTHLY_WDAY = 4;
    const RECUR_YEARLY       = 5;
    const RECUR_SECONDLY     = 6;
    const RECUR_MINUTELY     = 7;
    const RECUR_HOURLY       = 8;
    
    /**
     * stati of participants
     */
    const REJECTED    = 0;
    const NO_RESPONSE = 1;
    const TENTATIVE   = 2;
    const ACCEPTED    = 3;
    
    /**
     * weekdays bitfield
     */
    const SUNDAY    = 1;
    const MONDAY    = 2;
    const TUESDAY   = 4;
    const WEDNESDAY = 8;
    const THURSDAY  = 16;
    const FRIDAY    = 32;
    const SATURDAY  = 64;
    const WEEKDAYS  = 62;
	const WEEKEND   = 65;
	const ALLDAYS   = 127;
    
	public static $weekdayIsoMap = array(
        1 => self::MONDAY,
        2 => self::TUESDAY,
        3 => self::WEDNESDAY,
        4 => self::THURSDAY,
        5 => self::FRIDAY,
        6 => self::SATURDAY,
        7 => self::SUNDAY
	);
	
    protected $_events_table_name;
    protected $_dates_table_name;
    protected $_extra_table_name;
    protected $_repeats_table_name;
    protected $_user_table_name;
    
    /**
     * @var Zend_Db_Adapter
     */
    protected $_db;
    
    /**
     * Horizont of this complete installation.
     * The horizont is the time till which all repitions of recuring events are
     * stored into the sql backend tables (see class description)
     *
     * @var Zend_Date
     */
    protected $_horizont;
    
    /**
     * @todo query horizont from config!
     */
    public function __construct()
    {
        $this->_events_table_name = SQL_TABLE_PREFIX. 'cal';
        $this->_dates_table_name = SQL_TABLE_PREFIX. 'cal_dates';
        $this->_extra_table_name = SQL_TABLE_PREFIX. 'cal_extra';
        $this->_repeats_table_name = SQL_TABLE_PREFIX. 'cal_repeats';
        $this->_user_table_name = SQL_TABLE_PREFIX. 'cal_user';
        
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->_horizont = new Zend_Date('30-12-2008');
    }
    
    
    /**
     * Returns a resultset of calendar events matching $_query criteria
     *
     * @todo add user and other filters AND acl
     * @todo add support for alarms
     * @todo add custom fields
     * @todo slow where statement works better with union select but is not supported by ZF and mssql
     * 
     * @param Zend_Date $_start
     * @param Zend_Date $_end
     * @param Calendar_UserSet 
     * @param 
     * @return array of events
     */
    public function getEvents( Zend_Date $_start, Zend_Date $_end, $_users, $_filters )
    {
        // we need two querys to get the requested events.
        //   1. get all events for user(s) in requested timespan
        //   2. get partitipants for matching events from 1. step
        $select = $this->_db->select()
            ->distinct()
            ->from( array( 'events' => $this->_events_table_name ) )
            ->join( array( 'dates' => $this->_dates_table_name), 'events.cal_id = dates.cal_id' )
            ->join( array( 'user' => $this->_user_table_name ), 'events.cal_id = user.cal_id', array( 'cal_recur_date' ) )
            ->joinLeft( array( 'repeats' => $this->_repeats_table_name), 'events.cal_id = repeats.cal_id', array(
                'recur_type AS cal_recur_type', 'recur_enddate AS cal_recur_enddate', 
                'recur_interval AS cal_recur_interval', 'recur_data AS cal_recur_data', 'recur_exception AS cal_recur_exception'
            ))
            ->where('repeats.recur_type IS NULL AND user.cal_recur_date = 0 OR user.cal_recur_date = dates.cal_start')
            ->where('dates.cal_start >= ?', $_start->getTimestamp() )
            ->where('dates.cal_end <= ?' ,  $_end->getTimestamp() );
            
        $stmt = $this->_db->query($select);
        $events = new Calendar_EventSet();
        while ($event = $stmt->fetch()) {
            $eventObj = $this->_sql2event( $event );
            $events->addRecord($eventObj);
        }
        
        $this->_addParticipantsToEventsObjs($events);
        print_r($events->toArray());
        return $events;
    }
    

    /**
     * Returns an events with the specified id. If no repitition timestamp is 
     * in the id, the first event of an series is returned.
     * 
     * @todo ACL
     * @todo Arlarms
     * @todo custom fileds
     * @param int $_id
     * @retrun Calendar_Event
     */
    public function getEventById( $_id )
    {
    if (strpos($_id, '-') !== false) {
    		list( $id, $repitionDate) = explode( '-', $_id );
    	} else {
    		$id = $_id;
    		$repitionDate = 0;
    	}
    	
        $select = $this->_db->select();
            
        // if no repition date is set, we need the minimum start date to
        // get the initial event else we can do an exact query
        if ( $repitionDate == 0 ) {
            $select
                ->from( array( 'dates' => $this->_dates_table_name),
                    array('MIN(cal_start) AS cal_start', 'MIN(cal_end) AS cal_end') 
                )
                ->group('dates.cal_id');
        } else {
            $select
                ->from( array( 'dates' => $this->_dates_table_name) )
                ->where( 'cal_start = ?', $repitionDate );
        }

        $select
            ->where( 'dates.cal_id = ?', $_id )
            ->join(array( 'events' => $this->_events_table_name ), 'events.cal_id = dates.cal_id')
            ->joinLeft( array( 'repeats' => $this->_repeats_table_name), 'events.cal_id = repeats.cal_id', array(
                'recur_type AS cal_recur_type', 'recur_enddate AS cal_recur_enddate', 
                'recur_interval AS cal_recur_interval', 'recur_data AS cal_recur_data', 'recur_exception AS cal_recur_exception'
            ));
        $stmt = $select->query();
        $result = $stmt->fetchAll();
        $event = array_pop($result);
        
        // check if we got a usefull result
        if ( !empty($result) ) {
            throw new Calendar_Backend_FatalException('More than one enty found for this id. This must not happen!');
        } elseif ( empty( $event ) ) {
            throw new AccessViolationException('No Event found, either user is not permitted to view this event or there is no such event');
        }
        
        $event['cal_recur_date'] = $repitionDate;
        $eventObj = $this->_sql2event( $event );
        $this->_addParticipantsToEventsObjs( $eventObj );
        
        return $eventObj;
    }
    
    /**
     * returns an event object idenitfied by $_uid
     *
     * @param string $_uid
     * @return event
     * @throws getEntryFailed
     */
    public function getEventByUid( $_uid )
    {
        
    }
    
    /**
     * Saves an event to backend store
     * 
     * NOTE: In case of recuring events, only base events are accepted! For a single
     * recurance of an recuring event, you need to use the modifiy_participants and
     * modifiy_alarms methods! All other changes are exceptions!
     *
     * @todo replace somemagicnumber for uid!
     * @param event $_event
     * @return string uid of saved event
     * @throws saveEntryFailed
     */
    public function saveEvent( Calendar_Event $_event )
    {
        
    	if (strpos($_event->getId(), '-') !== false) {
    		throw new Exception('Can\'t save a special recurance, make an excetion instead' );
    	}

        $events_table = new Calendar_Backend_Sql_Events( array( 'name' => $this->_events_table_name ));
        $user_table = new Calendar_Backend_Sql_Events( array( 'name' => $this->_user_table_name ));
    	$dates_table = new Calendar_Backend_Sql_Events( array( 'name' => $this->_dates_table_name ));
    	$repeats_table = new Calendar_Backend_Sql_Events( array( 'name' => $this->_repeats_table_name ));
    	
    	$_event->cal_modified = Zend_Date::now();
    	$_event->cal_modifier = Zend_Registry::get('currentAccount');

    	$events_data = array(
    	    'cal_owner'        => $_event->cal_owner,
    	    'cal_category'     => $_event->cal_category,
    	    'cal_priority'     => $_event->cal_priority,
    	    'cal_public'       => $_event->cal_public,
    	    'cal_title'        => $_event->cal_title,
    	    'cal_description'  => $_event->cal_description,
    	    'cal_location'     => $_event->cal_location,
    	    'cal_reference'    => $_event->cal_reference,
    	    'cal_non_blocking' => $_event->cal_non_blocking,
    	    'cal_special'      => $_event->cal_special,
    	    'cal_modifier'     => $_event->cal_modifier,
    	    'cal_modified'     => $_event->cal_modified->getTimestamp()
    	);
    	
    	if ($_event->getId() === NULL) {
    	    $_event->setId($events_table->insert( $events_data ));
    	    $_event->uid = 'calendar-' . $_event->getId() . '-' . 'somemagicnumber';
    	    $events_table->update(array(
    	    	'cal_uid' => $_event->uid
    	    ), $this->_db->quoteInto('cal_id = (?)', $_event->getId() ) );
    	} else {
    	    $oldEvent = $this->getEventById($_event->getId());
    	    
    	    $numOfUpdates = $events_table->update($events_data, array(
        	    $this->_db->quoteInto('cal_id = (?)', $_event->getId() ),
        	    $this->_db->quoteInto('cal_modified = (?)', $oldEvent->cal_modified )
        	));
        	if ($numOfUpdates != 1) {
        	    // something went wrong!
        	    throw new Exception('TODO: Find out whats wrong!');
        	}
    	}
        
        $repetionData = array(
	        'recur_type'      => $_event->cal_recur_type,
	        'recur_enddate'   => $_event->cal_recur_enddate->getTimestamp(),
	        'recur_interval'  => $_event->cal_recur_interval,
	        'recur_data'      => $_event->cal_recur_data,
        );
       
    	if (isset($oldEvent)) {
    	    // argh! verry complicated!!!!!!!!!!!!!!!!!!!!!
    	    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    	    
    	} else {
            // we start an new event. This is the easy case :-)
    	    $repitions = $this->_computeRepitions( $_event );
    	    $repitions->addRecord($_event);
    	    
    	    if ($_event->cal_recur_type > 0) {
    	        $repetionData['cal_id'] = $_event->getId();
        	    $repeats_table->insert($repetionData);
        	}
    	    
    	    foreach ($repitions as $repition) {
    	        $repitionStartTs = $repition->cal_start->getTimestamp();
    	        $repitionStopTs = $repition->cal_stop->getTimestamp();

    	        $dates_table->insert( array(
    	            'cal_id' => $_event->getId(),
    	            'cal_start' => $repitionStartTs,
    	            'cal_end' => $repitionStopTs,
    	        ));
    	        
    	        $repitionBaseData = array(
    	          	'cal_id'         => $repition->getId(),
    	            'cal_recur_date' => $repitionStartTs
    	        );
    	        foreach ($repition->cal_participants as $participant) {
    	            $user_table->insert( array_merge( 
    	                $repitionBaseData,
    	                $participant
    	            ));
    	        }
    	    }
    	}
    }
    
    /**
     * Deletes Events from backend store, identified by there uids
     *
     * @param array $_events array of uids
     * @return void
     * @throws deleteEntryFailed
     */
    public function deleteEventsByUid( array $_events )
    {
        
    }
    
    /**
     * computes repitions for a given Calendar_Event
     * The number of repitions is limited by the recur_endate or the horizont
     * of this installation.
     * NOTE: We _dont_ change the events id's in here to <id>-<recurance> as
     * such an representaion is only needed outside this backend!
     *
     * @param Calendar_Event $event
     * @return Calendar_EventSet w.o. the given base event!
     */
    protected function _computeRepitions( Calendar_Event $event )
    {
        $events = new Calendar_EventSet();
        //$baseId = $event->getId();
        
        // recur_interval 0 in db actually means an interval of 1 (egw1.4 compat)
        $event->cal_recur_interval = $event->cal_recur_interval == 0 ? 1 : $event->cal_recur_interval;
        
        while( true ) {
            switch ( $event->cal_recur_type ) {
                case self::RECUR_NONE :
                    break 2;
                case self::RECUR_HOURLY :
                    $event->cal_start->add($event->cal_recur_interval, Zend_Date::HOUR);
                    $event->cal_end->add($event->cal_recur_interval, Zend_Date::HOUR);
                    break;
                case self::RECUR_DAILY :
                    $event->cal_start->add($event->cal_recur_interval, Zend_Date::DAY);
                    $event->cal_end->add($event->cal_recur_interval, Zend_Date::DAY);
                    break;
                case self::RECUR_WEEKLY :
                    $isoWday = $event->cal_start->toValue(Zend_Date::WEEKDAY_8601);
                    if (self::$weekdayIsoMap[$isoWday] == $event->cal_recur_data) {
                        // normal weekly recurance
                        $event->cal_start->add($event->cal_recur_interval, Zend_Date::WEEK);
                        $event->cal_end->add($event->cal_recur_interval, Zend_Date::WEEK);
                    } else {
                        // recurance on other day(s) than base event
                        for ($i=1; $i<7; $i++) {
                            $event->cal_start->add($event->cal_recur_interval, Zend_Date::DAY);
                            $isoWday = $event->cal_start->toValue(Zend_Date::WEEKDAY_8601);
                            if (self::$weekdayIsoMap[$isoWday] & $event->cal_recur_data) {
                                $event->cal_end->add($i, Zend_Date::DAY);
                                break;
                            }
                        }
                    }
                    break;
                case self::RECUR_MONTHLY_MDAY :
                    $event->cal_start->add($event->cal_recur_interval, Zend_Date::MONTH);
                    $event->cal_end->add($event->cal_recur_interval, Zend_Date::MONTH);
                    break 1;
                case self::RECUR_MONTHLY_WDAY :
                    // wdays are in the sence of "everey n'th wday of a month"
                    $isoMonth = $event->cal_start->toValue(Zend_Date::MONTH_SHORT);
                    $nWdayInMonth = ceil($event->cal_start->toValue(Zend_Date::DAY_SHORT)/7);
                    for ($i=1; $i<52; $i++) {
                        $event->cal_start->add($event->cal_recur_interval, Zend_Date::WEEK);
                        if (ceil($event->cal_start->toValue(Zend_Date::DAY_SHORT)/7) == $nWdayInMonth) {
                            $event->cal_end->add($i, Zend_Date::WEEK);
                            break;
                        }
                    }
                    break;
                case self::RECUR_YEARLY :
                    $event->cal_start->add($event->cal_recur_interval, Zend_Date::YEAR);
                    $event->cal_end->add($event->cal_recur_interval, Zend_Date::YEAR);
                    break;
                default :
                    throw new Exception("Recour Type {$row['cal_recur_type']} is not supported!");
            }
            
            //check that this date is not later than the requested horizont
            if ( $event->cal_start->compare($this->_horizont) > 0 ) {
                break 1;
            }
            
            //check that this date is not later than the enddate of recuring event
            if ($event->cal_recur_enddate instanceof Zend_Date && $event->cal_start->compare($event->cal_recur_enddate) > 0) {
                break 1;
            }
            
            //check that this date is no exception
            foreach ($event->cal_recur_exception as $exception) {
                if ($event->cal_start->compare($exception) == 0) {
                    continue 2;
                }
            }
            
            // if we come here, we have a valid recurance of the recuring event
            // lets add it to our recordSet and continue parsing this event
            // $event->setId($baseId . '-' . $event->cal_start->getTimestamp());
            $events->addRecord( clone( $event ) );
            continue;
        }
        return $events;
    }
    
    /**
     * Get all repitions (not the base events) of recouring events in the 
     * given time frame.
     * 
     * @param Zend_Date $_start
     * @param Zend_Date $_end
     * @param Calendar_UserSet 
     * @param  
     */
    function getRepitions( Zend_Date $_start, Zend_Date $_end, $_users, $_filters )
    {
        //get all recouring base events with cal_start, cal_end and recour_enddate!
        $select = $this->_db->select()
            ->from( array( 'dates' => $this->_dates_table_name),
                array('dates.cal_id', 'MIN(dates.cal_start) AS cal_start', 'MIN(dates.cal_end) AS cal_end')
            )
            ->group('dates.cal_id')
            ->join( array( 'repeats' => $this->_repeats_table_name), 'dates.cal_id = repeats.cal_id', array(
                'recur_type AS cal_recur_type', 'recur_enddate AS cal_recur_enddate', 
                'recur_interval AS cal_recur_interval', 'recur_data AS cal_recur_data', 'recur_exception AS cal_recur_exception'
            ))
            ->join( array( 'events' => $this->_events_table_name ), 'dates.cal_id = events.cal_id' );
        
        // relevant are events which
        // - recour_enddate greater than $_start AND cal_start less than $_end
        // - no recour_enddate is set AND cal_start less than $_end
        $select
            ->where('repeats.recur_enddate > ?', $_start->getTimestamp() )
            ->orWhere('repeats.recur_enddate = 0')
            ->where('dates.cal_start < ?', $_end->getTimestamp() );
        
        $events = new Calendar_EventSet();
        $stmt = $this->_db->query($select);

        while ($row = $stmt->fetch()) {
            $event = $this->_sql2event($row);
            $events->merge($this->_computeRepitions($event));
        }
        return $events;
    }
    
    /**
     * gets participants of events. 
     * 
     * For fetching participants allways a second query has to be done. 
     * There is no way to get complete events in one query in a database
     * independent way!
     * 
     * @param mixed Calendar_Event|Calendar_EventSet
     */
    protected function _addParticipantsToEventsObjs( $_events )
    {
        if ( $_events instanceof Calendar_Event ) {
            $_events = new Calendar_EventSet( array($_events) );
            $_isEventSet = false;
        } else {
            $_isEventSet = true;
        }
        
        // fetch ids and repitionsDates from EventSet
    	foreach ( $_events as $event ){
        	if (strpos($event->getID(), '-') !== false) {
        		list( $id, $repitionDate) = explode( '-', $event->cal_id );
        	} else {
        		$id = $event->getID();
        		$repitionDate = 0;
        	}
            $ids[] = $id;
            $repitionDates[] = $repitionDate;
        }
        
        // query the participants
        $this->_db = Zend_Registry::get('dbAdapter');
        $select = $this->_db->select()
            ->from( array( 'user' => $this->_user_table_name ) )
            ->where( 'user.cal_id IN ( '. implode( ',', array_unique( $ids ) ). ' )' )
            ->where( 'user.cal_recur_date IN ( '. implode( ',', array_unique ( $repitionDates ) ). ' )' )
            ->order( 'cal_id' )
            ->order( 'cal_user_type DESC' );
        $stmt = $this->_db->query($select);

        // add participants to EventSet
        while ($row = $stmt->fetch()) {
            $id = $row['cal_id'];
            if ( $row['cal_recur_date'] > 0) {
                $id .= '-'. $row['cal_recur_date'];
            }
            if (!isset($_events[$id])) continue;
            $_events[$id]->setParticipant($row['cal_user_type'], $row['cal_user_id'],  $row['cal_status'], $row['cal_quantity']); 
        }
        
        if (!$_isEventSet) {
            $_events = $_events[$id];
        }
    }
    
    /**
     * Converts raw database result arrays into event objects
     * 
     * @param array $eventsArray raw result array
     * @return Calendar_Event
     */
    protected function _sql2event( $eventArray )
    {
        if ( array_key_exists('cal_recur_date', $eventArray) && $eventArray['cal_recur_date'] > 0 ) {
            // extend ids with recur_date for recuring events
            $eventArray['cal_id'] .= $eventArray['cal_recur_date'] > 0 ? '-'. $eventArray['cal_recur_date'] : '';
        }     
        
        // initialize recour_exceptions and alarms
        $eventArray['cal_recur_exception'] = $eventArray['cal_recur_exception'] ? explode( ',', $eventArray['cal_recur_exception'] ) : array();
        
        // transform dates
        foreach ( array('cal_modified', 'cal_start', 'cal_end', 'cal_recur_date', 'cal_recur_enddate' ) as $fieldname) {
            if (!array_key_exists($fieldname, $eventArray) || !$eventArray[$fieldname]) {
                $eventArray[$fieldname] = NULL;
            } else {
                $eventArray[$fieldname] = new Zend_Date($eventArray[$fieldname], Zend_Date::TIMESTAMP);
            }
        }
        foreach ($eventArray['cal_recur_exception'] as $num => $exceptiondate){
            $eventArray['cal_recur_exception'][$num] = new Zend_Date($exceptiondate, Zend_Date::TIMESTAMP);
        }
        $eventArray['cal_alarm'] = array();
        $eventArray['cal_participants'] = array();
        
        // create Calendar_Event
        return new Calendar_Event($eventArray, true);
    }
    
    /*
    protected function _event2sql( $event )
    {
        $eventArray = $event->toArray();
        foreach ( array('cal_modified', 'cal_start', 'cal_end', 'cal_recur_date', 'cal_recur_enddate' ) as $fieldname) {
            $eventArray[$fieldname] = $eventArray[$fieldname] instanceof Zend_Date ? $eventArray[$fieldname]->getTimestamp() : NULL;
        }
        foreach ($eventArray['cal_recur_exception'] as $num => $exceptiondate){
            $eventArray['cal_recur_exception'][$num] = $exceptiondate->getTimestamp();
        }
        $eventArray['cal_recur_exception'] = implode(',', $eventArray['cal_recur_exception']);
        
        return $eventArray;
    }
    */
    
}