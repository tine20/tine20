<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * attendee busy exception
 *
 * @package Calendar
 */
class Calendar_Exception_AttendeeBusy extends Exception
{
    /**
     * free busy info
     * 
     * @var Tinebase_Record_RecordSet
     */
	protected $_fbInfo = NULL;
	
	/**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'event attendee busy conflict', $_code = 901) {
        parent::__construct($_message, $_code);
    }
    
    /**
     * set fb info
     * 
     * @param Tinebase_Record_RecordSet $_fbInfo
     */
	public function setFreeBusyInfo(Tinebase_Record_RecordSet $_fbInfo)
	{
	   $this->_fbInfo = $_fbInfo;
	}
	
	/**
	 * get fb info
	 * 
	 * @return Tinebase_Record_RecordSet
	 */
	public function getFreeBusyInfo()
	{
	    return $this->_fbInfo ? $this->_fbInfo : new Tinebase_Record_RecordSet('Calendar_Model_FreeBusy');
	}
	
	/**
	 * returns free busy info as array
	 * 
	 * @return array
	 */
    public function toArray()
    {
        $this->getFreeBusyInfo()->setTimezone(Tinebase_Core::get('userTimeZone'));
        return array(
            'freebusyinfo' => $this->getFreeBusyInfo()->toArray()
        );
    }
}
