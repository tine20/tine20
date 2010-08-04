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
 * class to store Sieve rule condition and to generate Sieve code for condition
 * 
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Rule_Condition
{
    const COMPERATOR_CONTAINS   = 'contains';
    const COMPERATOR_OVER       = 'over';
    const COMPERATOR_UNDER      = 'under';
    
    const TEST_ADDRESS  = 'address';
    const TEST_HEADER   = 'header';
    const TEST_SIZE     = 'size';

    /**
     * what to test
     * 
     * @var string
     */
    protected $_test;
    
    /**
     * how to compare
     * 
     * @var string
     */
    protected $_comperator;
    
    /**
     * which header to check
     * 
     * @var string
     */
    protected $_header;
    
    /**
     * key to match against
     * 
     * @var string
     */
    protected $_key;
    
    /**
     * set test
     * 
     * @param   string  $test   the test
     * @return  Felamimail_Sieve_Rule_Condition
     */
    public function setTest($test)
    {
        $this->_test = $test;
        
        return $this;
    }
    
    /**
     * set how to compare
     * 
     * @param   string      $comperator     the comparator
     * @return  Felamimail_Sieve_Rule_Condition
     */
    public function setComperator($comperator)
    {
        if(!defined('self::COMPERATOR_' . strtoupper($comperator))) {
            throw new InvalidArgumentException('invalid comperator: ' . $comperator);
        }
        $this->_comperator = $comperator;
        
        return $this;
    }
    
    /**
     * the header to check
     * 
     * @param   string  $header     the header
     * @return  Felamimail_Sieve_Rule_Condition
     */
    public function setHeader($header)
    {
        $this->_header = $header;
        
        return $this;
    }
    
    /**
     * set key to match against
     * 
     * @param   string  $key    the key to match against
     * @return  Felamimail_Sieve_Rule_Condition
     */
    public function setKey($key)
    {
        $this->_key = $key;
        
        return $this;
    }
    
    /**
     * return the Sieve code for this condition
     * 
     * @return string
     */
    public function __toString() 
    {
        switch($this->_test) {
            case self::TEST_SIZE:
                return "$this->_test :$this->_comperator $this->_key";
                break;
                
            default:
                $header = $this->_quoteString($this->_header);
                $key = $this->_quoteString($this->_key);
                return "$this->_test :$this->_comperator $header $key";
                break;
        }
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
    
    /**
     * return values as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'test'            => $this->_test,
            'comperator'      => $this->_comperator,
            'header'          => $this->_header,
            'key'             => $this->_key,
        );
    }
}
