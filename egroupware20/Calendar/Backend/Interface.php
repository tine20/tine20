<?php

/**
 * interface for calendar backends
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Lists.php 121 2007-09-24 19:42:55Z lkneschke $
 *
 */

interface Calendar_Backend_Interface
{
    /**
     * Returns a resultset of calendar events matching $_query criteria
     *
     * @param array $_query
     * @return array of events
     */
    public function getEvents( Zend_Date $_start, Zend_Date $_end, $_users, $_filters );
    
    /**
     * returns an event object idenitfied by $_uid
     *
     * @param string $_uid
     * @return event
     * @throws getEntryFailed
     */
    public function getEventByUid( $_uid );
    
    /**
     * Saves an event to backend store
     *
     * @param event $_event
     * @return string uid of saved event
     * @throws saveEntryFailed
     */
    public function saveEvent( Calendar_Event $_event );
    
    /**
     * Deletes Events from backend store, identified by there uids
     *
     * @param array $_events array of uids
     * @return void
     * @throws deleteEntryFailed
     */
    public function deleteEventsByUid( array $_events );
    
}