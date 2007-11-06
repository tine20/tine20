<?php

/**
 * json interface for calendar
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Calendar_Json extends Egwbase_Application_Json_Abstract
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

    public function getEvents( $_start, $_end, $_users, $_filters )
    {
        //$now = new Zend_Date();
        //$etime = $now->add(60, Zend_Date::DAY);
        
        //$events = $this->_backend->getEvents( new Zend_Date('01-10-2007') , new Zend_Date('30-12-2007'), 'r1', array() );
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
        
        echo 'hallo';
        //print_r($this->getEventById(5));
        
    }
    
    public function getEventById( $_id )
    {
        echo 'hallo';
        $event = $this->_backend->getEventById( $_id );
        self::date2Iso($event);
        return $event->toArray();
    }
    
    public function saveEvent( $_event )
    {
        
    }
    
    public function deleteEventsById( $_events )
    {
        
    }
    
    public function getInitialTree()
    {
        $treeNodes = array();

        $treeNode = new Egwbase_Ext_Treenode('Calendar', 'mycalendar', 'mycalendar', 'My Calendar', TRUE);
        $treeNode->cls = 'treemain';
        $treeNode->jsonMethod = 'Admin.getApplications';
        $treeNode->dataPanelType = 'applications';
        $treeNodes[] = $treeNode;

        return $treeNodes;
    }
    
    /**
     * converts Zend_Dates into iso format for json transport
     *
     * @param iteratable $toConvert
     */
    public static function date2Iso( $toConvert )
    {
        foreach ($toConvert as $field => $value) {
            if ($value instanceof Zend_Date) {
                $toConvert[$field] = $value->getIso();
            } elseif (is_array($value)) {
                $toConvert[$field] = self::date2Iso($value);
            }
        }
    }
}