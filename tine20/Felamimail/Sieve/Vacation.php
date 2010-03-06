<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * class to store vacation setting and to generate Sieve code for vacations
 * 
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Vacation
{    
    /**
     * period in which addresses are kept and are not responded to
     * 
     * @var integer
     */
    protected $_days = 7;
    
    /**
     * emailaddresses which belong to the recipient
     * 
     * @var array
     */
    protected $_addresses = array();
    
    /**
     * vacation message
     * 
     * @var string
     */
    protected $_reason;
    
    /**
     * status of vacation (enabled or disabled)
     * 
     * @var boolean
     */
    protected $_enabled = false;
    
    /**
     * the from address for the generated messsage
     * 
     * @var string
     */
    protected $_from;
    
    /**
     * the mime type of the generated message
     * 
     * @var string
     */
    protected $_mime;
    
    /**
     * the unique identifier of the vacaction message
     * 
     * @var string
     */
    protected $_handle;
    
    /**
     * the subject for the vacation message
     * 
     * @var string
     */
    protected $_subject;
    
    /**
     * set from 
     * 
     * @param   string  $from   the from address
     * @return  Felamimail_Sieve_Vacation
     */
    protected function setFrom($from)
    {
        $this->_from = $from;
        
        return $this;
    }
    
    /**
     * set subject
     * 
     * @param   string  $subject    the subject
     * @return  Felamimail_Sieve_Vacation 
     */
    protected function setSubject($subject)
    {
        $this->_subject = $subject;
        
        return $this;
    }
    
    /**
     * set the days
     * 
     * @param   integer $days   the days
     * @throws  InvalidArgumentException
     * @return  Felamimail_Sieve_Vacation
     */
    public function setDays($days)
    {
        if(!ctype_digit("$days")) {
            throw new InvalidArgumentException('$days must be numbers only' . $days);
        }
        
        $this->_days = $days;
        
        return $this;
    }
    
    /**
     * add address
     * 
     * @param   string  $address    the address
     * @return  Felamimail_Sieve_Vacation
     */
    public function addAddress($address)
    {
        $this->_addresses[] = $address;
        
        return $this;
    }
    
    /**
     * set reason
     * 
     * @param   string  $reason     the reason
     * @return  Felamimail_Sieve_Vacation
     */
    public function setReason($reason)
    {
        $this->_reason = $reason;
        
        return $this;
    }
    
    /**
     * set status
     * 
     * @param   boolean $status     the status
     * @return  Felamimail_Sieve_Vacation
     */
    public function setEnabled($status)
    {
        $this->_enabled = (bool) $status;
        
        return $this;
    }
    
    /**
     * return if vacation is enabled
     * 
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }
    
    /**
     * return the vacation Sieve code
     * 
     * @return string
     */
    public function __toString() 
    {
        $vacation = sprintf("vacation :days %d :addresses %s text:\r\n%s\r\n.\r\n;",
            $this->_days,
            $this->_quoteString($this->_addresses),
            $this->_reason
        );
        
        return $vacation;
    }
       
    /**
     * quote string for usage in Sieve script 
     * 
     * @param   string  $string     the srting to quote
     */
    protected function _quoteString($string)
    {
        if(is_array($string)) {
            $string = array_map(array($this, '_quoteString'), $string);
            return '[' . implode(',', $string) . ']'; 
        } else {
            return '"' . str_replace('"', '\""', $string) . '"';
        }
    }
}