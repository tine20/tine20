<?php
class Zend_Auth_Http_Ntlm_Identity
{
    /**
     * @var int flags
     */
    protected $_flags = 0;
    
    public function __construct(array $idData = array())
    {
        if (array_key_exists('flags', $idData)) {
            $this->_flags = $idData['flags'];
        }
    }
    
    /**
     * get response flags
     * 
     * @return int
     */
    public function getFlags()
    {
        return $this->_flags;
    }
    
    /**
     * checks if flag is set in response flags
     * 
     * @param  int $flag
     * @return bool
     */
    public function hasFlag($flag)
    {
        return (bool) ($this->getFlags() & $flag);
    }
}