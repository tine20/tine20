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
 * class to store Sieve rule action and to generate Sieve code for action
 * 
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Rule_Action
{
    const DISCARD   = 'discard';
    const FILEINTO  = 'fileinto';
    const KEEP      = 'keep';
    const REJECT    = 'reject';
    const REDIRECT  = 'redirect';
    
    /**
     * type of action
     * 
     * @var string
     */
    protected $_type;
    
    /**
     * argument for action
     * 
     * @var string
     */
    protected $_argument;
    
    /**
     * set type of action
     * 
     * @param   string  $type   type of action
     * @return  Felamimail_Sieve_Rule_Action
     */
    public function setType($type)
    {
        if(!defined('self::' . strtoupper($type))) {
            throw new InvalidArgumentException('invalid type: ' . $type);
        }
        $this->_type = $type;
        
        return $this;
    }
    
    /**
     * set argument for action
     * 
     * @param   string  $argument   argument
     * @return  Felamimail_Sieve_Rule_Action
     */
    public function setArgument($argument)
    {
        $this->_argument = $argument;
        
        return $this;
    }
    
    /**
     * return the Sieve code for this action
     * 
     * @return string
     */
    public function __toString() 
    {
        switch($this->_type) {
            case self::DISCARD:
                return "    $this->_type;";
                break;
                
            default:
                $argument = $this->_quoteString($this->_argument);
                return "    $this->_type $argument;";
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
            'type'            => $this->_type,
            'argument'        => $this->_argument,
        );
    }
}