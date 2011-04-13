<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * LDAP infomation class
 * 
 * @package     Tinebase
 */
class Tinebase_LdapInfo
{
    /**
     * openldap server
     */
    const TYPE_OPENLDAP = 'openldap';

    /**
     * unknown server
     */
    const TYPE_UNKNOWN  = 'unknown';
    
    
    protected $_serverType = NULL;
    
    protected $_supportedProtocolVersion = NULL;
    
    protected $_supportedObjectClasses = NULL;
    
    protected $_subschemaEntryDn = NULL;
    
    protected $_namingContexts = NULL;
    
    /**
     * constructs a new ldap info object
     *
     * @param Tinebase_Ldap $_ldapServer
     */
    public function __construct(Tinebase_Ldap $_ldapServer)
    {
        $this->_ldapServer = $_ldapServer;
        
        $this->_resolveBaseInfo();
    }
    
    public function getType()
    {
        return $this->_serverType;
    }
    
    public function getProtocolVersion()
    {
        return $this->_supportedProtocolVersion;
    }
    
    public function getSupportedObjectClasses()
    {
        if (! $this->_supportedObjectClasses) {
            $this->_resolveSupportedObjectClasses();
        }
        
        return $this->_supportedObjectClasses;
    }
    
    /**
     * resolves base info and filles them into the corrensponding properties of object
     */
    protected function _resolveBaseInfo()
    {
        $dn = '';
        $filter='(objectclass=*)';
        $attributes = array(
            'structuralObjectClass',
            'namingContexts',
            'supportedLDAPVersion',
            'subschemaSubentry'
        );
        
        $info = $this->_ldapServer->fetchDn($dn, $filter, $attributes);
        
        // find servder type
        if($info[0]['namingcontexts']) {
            for($i=0; $i<$info[0]['namingcontexts']['count']; $i++) {
                $namingcontexts[] = $info[0]['namingcontexts'][$i];
            }
            $this->_namingContexts = $namingcontexts;
        }

        // find servder type
        if($info[0]['structuralobjectclass']) {
            switch($info[0]['structuralobjectclass'][0]) {
                case 'OpenLDAProotDSE':
                    $this->_serverType = self::TYPE_OPENLDAP;
                    break;
                default:
                    $this->_serverType = self::TYPE_UNKNOWN;
                    break;
            }
            
        }
        
        // find subschema entry dn
        if($info[0]['subschemasubentry']) {
            $this->_subschemaEntryDn = $info[0]['subschemasubentry'][0];
        }
        
    }
    
    /**
     * resolves supported object classes
     */
    protected function _resolveSupportedObjectClasses()
    {
        if(empty($this->_subschemaEntryDn)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . "  Could not resolve supported object classes");
            return;
        }
        
        $filter     = '(objectclass=*)';
        $attributes = array('objectClasses');
        
        $info = $this->_ldapServer->fetchDn($dn, $filter, $attributes);
        
        if($info[0]['objectclasses']) {
            for($i=0; $i<$info[0]['objectclasses']['count']; $i++) {
                $pattern = '/^\( (.*) NAME \'(\w*)\' /';
                if(preg_match($pattern, $info[0]['objectclasses'][$i], $matches)) {
                    #_debug_array($matches);
                    if(count($matches) == 3) {
                        $supportedObjectClasses[$matches[1]] = strtolower($matches[2]);
                    }
                }
            }

            $this->_supportedObjectClasses = $supportedObjectClasses;
        }
    }
}
