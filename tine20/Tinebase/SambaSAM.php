<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class SambaSAM
 * 
 * Samba Account Managing
 * 
 * @package Tinebase
 * @subpackage Samba
 */
class SambaSAM
{
    
    /**
     * holds the instance of the singleton
     *
     * @var SambaSAM
     */
    private static $_instance = NULL;
    
    /**
     * holds crypt engine
     *
     * @var Crypt_CHAP_MSv1
     */
    protected $_cryptEngine = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
        
    }
    
    /**
     * the singleton pattern
     *
     * @return SambaSAM
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new SambaSAM();
        }
        
        return self::$_instance;
    }
    
    /**
     * returns crypt engine for NT/LMPasswords
     *
     * @return Crypt_CHAP_MSv1
     */
    protected function _getCryptEngine()
    {
        if (! $this->_cryptEngine) {
            $this->_cryptEngine = new Crypt_CHAP_MSv1();
        }
        
        return $this->_cryptEngine;
    }
    
    /**
     * generates LM password
     *
     * @param  string $_password uncrypted original password
     * @return string LM password
     */        
    protected function _generateLMPasswords($_password)
    {
        $lmPassword = strtoupper(bin2hex($this->_getCryptEngine()->lmPasswordHash($_password)));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $lmPassword: ' . $lmPassword);
        
        return $lmPassword;
    }
    
    /**
     * generates NT password
     *
     * @param  string $_password uncrypted original password
     * @return string NT password
     */ 
    protected function _generateLNTPasswords($_password)
    {
        $ntPassword = strtoupper(bin2hex($this->_getCryptEngine()->ntPasswordHash($_password)));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $ntPassword: ' . $ntPassword);
        
        return $ntPassword;
    }
}