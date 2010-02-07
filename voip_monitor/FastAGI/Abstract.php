<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 */
abstract class FastAGI_Abstract
{
    /**
     * the fastagi connection (aka the socket handling object)
     * 
     * @var FastAGI
     */
    protected $_fastAGI;
    
    /**
     * holds the agi variables as provided by Asterisk
     * 
     * @var array
     */
    protected $_agiVariables;
    
    /**
     * 
     * 
     * @var Zend_Config
     */
    protected $_config;
    
    /**
     * 
     * @var Zend_Cache_Core
     */
    protected $_cache;
    
    /**
     * the constructor
     *  
     * @param FastAGI $_fastAGI
     * @param array $_variables
     */
    public function __construct(FastAGI $_fastAGI, array $_variables)
    {
        $this->_fastAGI      = $_fastAGI;
        $this->_agiVariables = $_variables;
        $this->_config       = $this->_fastAGI->getConfig();
        
        $this->_initializeCache($this->_config->general);
    }
    
    protected function _initializeCache(Zend_Config $_config)
    {
        $frontendOptions = array(
            'lifetime'                => 3600,
            'automatic_serialization' => true
        );
        $backendOptions  = array();
        
        if(($path = $_config->get('cachedir')) !== null) {
            if(extension_loaded('SQLite')) {
                $backendType     = 'Sqlite';
                $backendOptions  = array(
                    'cache_db_complete_path' => $path . '/fastagi_cache.sqlite'
                );
            } else {
                $backendType     = 'File';
                $backendOptions  = array(
                    'cache_dir' => $path
                );
            }
        } else {
            $backendType     = 'Test';
            $frontendOptions = array(
                'caching' => false
            );
        }
        $this->_cache = Zend_Cache::factory('Core',
                             $backendType,
                             $frontendOptions,
                             $backendOptions);
    }
    
    /**
     * extract sip peername (SIP/XXX-yyyyy => XXX)
     * 
     * @param string $_sipPeer
     * @return string
     */
    protected function _getSipPeer($_sipPeer = null)
    {
        $sipPeer = $_sipPeer !== null ? $_sipPeer : $this->_agiVariables['agi_channel'];
        if(!preg_match('#SIP/(.*)-\w#', $sipPeer, $matches)) {
            throw new Exception('invalid sip peer name provided ' . $sipPeer);
        }
        
        return $matches[1];
    }
}
