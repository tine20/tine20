<?php
class Zend_Auth_Http_Ntlm_Identity
{
    /**
     * @var int flags
     */
    protected $_flags = 0;
    
    protected $_domain = NULL;
    protected $_workstation = NULL;
    
    public function __construct(array $idData = array())
    {
        if (array_key_exists('flags', $idData)) {
            $this->_flags = $idData['flags'];
        }
        
    if (array_key_exists('ntlmData', $idData)) {
        
            $which = array('domain', 'workstation');
            foreach( (array) $idData['ntlmData'] as $key => $value) {
                if (in_array($key, $which)) {
                    $var = '_' . $key;
                    $this->$var = $value;
                }
            }
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
    
    public function getDomain()
    {
        return $this->_domain;
    }
    
    public function getWorkstation()
    {
        return $this->_workstation;
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