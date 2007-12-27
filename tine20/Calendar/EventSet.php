<?php
/**
 * Implemetation of an eventet
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Calendar_EventSet extends Egwbase_Record_RecordSet
{
    //protected $_recordClass = 'Calendar_Event';
    
/**
     * Merges given RecordSet into this Set
     *
     * @param RecordSet 
     */
    public function merge( Calendar_EventSet $_eventSet )
    {
        $this->_listOfRecords = array_merge($this->_listOfRecords, $_eventSet);
    }
}