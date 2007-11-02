<?php

/**
 * json interface for calendar
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Lists.php 121 2007-09-24 19:42:55Z lkneschke $
 *
 */

class Calendar_Json
{
    /**
     * @var Calendar_Backend_Sql
     */
    protected $_backend;
    
    public function __construct()
    {
        $this->_backend = new Calendar_Backend_Sql();
    }

    public function getEvents()
    {
        $now = new Zend_Date();
        $etime = $now->add(60, Zend_Date::DAY);
        
        //$events = $this->_backend->getRepitions( new Zend_Date('01-10-2007') , new Zend_Date('30-12-2007'), 5, array() );
        //$events = $this->_backend->getEventById( 5 );
        //print_r($events->toArray());
        $event = $this->_backend->getEventById('5-1192788000');
        echo $event->cal_title. '<br>';
        
        $event->cal_title = time();
        echo $event->cal_title. '<br>';
        $this->_backend->saveEvent($event);
        
        
        //print_r($$event->toArray());
        
    }
    
}