<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * json interface for calendar
 * @package     Calendar
 */
class Calendar_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Calendar';
    
    /**
     * @var Calendar_Backend_Sql
     */
    protected $_backend;
    
    public function __construct()
    {
        $this->_backend = new Calendar_Backend_Sql();
    }

    public function getEvents( $start, $end, $users, $filters )
    {
        //$now = new Zend_Date();
        //$etime = $now->add(60, Zend_Date::DAY);
        
        $events = $this->_backend->getEvents( 
            new Zend_Date($start,Zend_Date::ISO_8601), 
            new Zend_Date($end,Zend_Date::ISO_8601), 
            5, 
            array() 
        );
        $jsonEvents = array();
        foreach ($events as $event) {
            $jsonEvents[] = self::date2Iso( $event->toArray() );
        }
        //exit;
        //$events = $this->_backend->getEventById( 5 );
        //print_r($events->toArray());
        //$event = $this->_backend->getEventById('5-1192788000');
        //$event = $this->_backend->getEventById('1');
        //print_r($event->toArray());
        //exit;
        
        //echo $event->cal_title. '<br>';
        
        //$event->cal_title = time();
        //echo $event->cal_title. '<br>';
        //$this->_backend->saveEvent($event);
        
        //echo 'hallo';
        //print_r($this->getEventById(5));
        return array(
            'results' => $jsonEvents,
            'totalcount' => count($events),
        );
    }
    
    public function getEventById( $id )
    {
        $event = $this->_backend->getEventById( $id );
        self::date2Iso($event);
        return $event->toArray();
    }
    
    public function saveEvent( $event )
    {
        
    }
    
    public function deleteEventsById( $events )
    {
        
    }
    
    public function getInitialTree()
    {
        $treeNodes = array();

        $treeNode = new Tinebase_Ext_Treenode('Calendar', 'mycalendar', 'mycalendar', 'My Calendar', TRUE);
        $treeNode->cls = 'treemain';
        $treeNode->jsonMethod = 'Admin.getApplications';
        $treeNode->dataPanelType = 'applications';
        $treeNodes[] = $treeNode;

        return $treeNodes;
    }
    
    /**
     * converts all Zend_Dates in an iteratable object
     * into iso format for json transport
     *
     * @param iteratable $_toConvert
     * @return iteratable converted $_toConvert
     */
    public static function date2Iso( $_toConvert )
    {
        foreach ($_toConvert as $field => $value) {
            if ($value instanceof Zend_Date) {
                $_toConvert[$field] = $value->getIso();
            } elseif (is_array($value)) {
                $_toConvert[$field] = self::date2Iso($value);
            }
        }
        return $_toConvert;
    }
}