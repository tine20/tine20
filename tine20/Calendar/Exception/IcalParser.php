<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * ical parser error
 *
 * @package Calendar
 */
class Calendar_Exception_IcalParser extends Exception
{
    /**
     * @var Exception
     */
    protected $_parseError = NULL;
    
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'can not parse ical file => syntax errors ?', $_code = 970) {
        parent::__construct($_message, $_code);
    }
    
    /**
     * set parse error
     * 
     * @param Exception $_e
     */
    public function setParseError(Exception $_e)
    {
       $this->_parseError = $_e;
    }
    
    /**
     * get parse error
     * 
     * @return Exception
     */
    public function getFreeBusyInfo()
    {
        return $this->_parseError;
    }

}
