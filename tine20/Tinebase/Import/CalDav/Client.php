<?php

/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase_Import_CalDav
 * 
 * @package     Tinebase
 * @subpackage  Import
 * 
 */
class Tinebase_Import_CalDav_Client extends \Sabre\DAV\Client
{
    /**
     * used to overwrite default retry behavior (if != null)
     * 
     * @var integer
     */
    protected $_requestTries = null;
    
    protected $currentUserPrincipal = '';
    protected $calendarHomeSet = '';
    protected $principals = array();
    protected $principalGroups = array();
    
    protected $requestLogFH;
    
    const findCurrentUserPrincipalRequest = 
'<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:current-user-principal />
  </d:prop>
</d:propfind>';

    const findCalendarHomeSetRequest =
'<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <x:calendar-home-set xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  </d:prop>
</d:propfind>';
    
    const resolvePrincipalRequest =
'<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:group-member-set />
    <d:displayname />
  </d:prop>
</d:propfind>';
    
    public function __construct(array $a)
    {
        parent::__construct($a);
        
        //$this->requestLogFH = fopen('/var/log/tine20/requestLog', 'w');
        
        $this->propertyMap['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] = 'Sabre\CalDAV\Property\SupportedCalendarComponentSet';
        $this->propertyMap['{DAV:}acl'] = 'Sabre\DAVACL\Property\Acl';
        $this->propertyMap['{DAV:}group-member-set'] = 'Tinebase_Import_CalDav_GroupMemberSet';
    }
    
    /**
     * findCurrentUserPrincipal
     * - result ($this->currentUserPrincipal) is cached for 1 week
     * 
     * @param number $tries
     * @return boolean
     */
    public function findCurrentUserPrincipal($tries = 1)
    {
        $cacheId = convertCacheId('findCurrentUserPrincipal' . $this->userName);
        if (Tinebase_Core::getCache()->test($cacheId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' Loading user principal from cache');
            
            $this->currentUserPrincipal = Tinebase_Core::getCache()->load($cacheId);
            $user = $this->_setUser();
            if (! $user) {
                return false;
            }
            
            return true;
        }
        
        $result = $this->calDavRequest('PROPFIND', '/principals/', self::findCurrentUserPrincipalRequest, 0, $tries);
        if (isset($result['{DAV:}current-user-principal']))
        {
            $this->currentUserPrincipal = $result['{DAV:}current-user-principal'];
            $user = $this->_setUser();
            if (! $user) {
                return false;
            }
            
            Tinebase_Core::getCache()->save($this->currentUserPrincipal, $cacheId, array(), /* 1 week */ 24*3600*7);
            return true;
        }
        
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find current users principal');
        return false;
    }
    
    protected function _setUser()
    {
        try {
            $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $this->userName, 'Tinebase_Model_FullUser');
            Tinebase_Core::set(Tinebase_Core::USER, $user);
            $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($this->userName, $this->password);
            Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
        } catch (Tinebase_Exception_NotFound $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t find tine20 user: ' . $this->userName);
            return null;
        }
        
        $this->principals[$this->currentUserPrincipal] = $user;
        
        return $user;
    }
    
    public function findCurrentUserPrincipalForUsers(array &$users)
    {
        foreach ($users as $username => $pwd) {
            $this->userName = $username;
            $this->password = $pwd;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' Find principal for user ' . $this->userName);
            try {
                if (! $this->findCurrentUserPrincipal()) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                        . ' Skipping ' . $username);
                    unset($users[$username]);
                }
            } catch (Tinebase_Exception $te) {
                // TODO should use better exception (Not_Authenticatied, ...)
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                        . ' Skipping ' . $username);
                unset($users[$username]);
            }
        }
        return count($users) > 0;
    }
    
    /**
     * findCalendarHomeSet
     * - result ($this->calendarHomeSet) is cached for 1 week
     * 
     * @return boolean
     */
    public function findCalendarHomeSet()
    {
        if ('' == $this->currentUserPrincipal && ! $this->findCurrentUserPrincipal(/* tries = */ 3)) {
            return false;
        }
        $cacheId = convertCacheId('findCalendarHomeSet' . $this->userName);
        if (Tinebase_Core::getCache()->test($cacheId)) {
            $this->calendarHomeSet = Tinebase_Core::getCache()->load($cacheId);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' Loading user home set from cache');
            return true;
        }
        
        $result = $this->calDavRequest('PROPFIND', $this->currentUserPrincipal, self::findCalendarHomeSetRequest);
        
        if (isset($result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'])) {
            $this->calendarHomeSet = $result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'];
            Tinebase_Core::getCache()->save($this->calendarHomeSet, $cacheId, array(), /* 1 week */ 24*3600*7);
            return true;
        }
        
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find calendar homeset');
        return false;
    }
    
    /**
     * resolve principals
     * 
     * @param array $privileges
     */
    public function resolvePrincipals(array $privileges)
    {
        foreach ($privileges as $ace)
        {
            if ( $ace['principal'] == '{DAV:}authenticated' || $ace['principal'] == $this->currentUserPrincipal ||
                 isset($this->principals[$ace['principal']]) || isset($this->principalGroups[$ace['principal']])) {
                     continue;
            }
            
            $result = $this->calDavRequest('PROPFIND', $ace['principal'], self::resolvePrincipalRequest);
            if (isset($result['{DAV:}group-member-set'])) {
                $principals = $result['{DAV:}group-member-set']->getPrincipals();
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                        . ' ' . print_r($principals, true));
                $this->principalGroups[$ace['principal']] = $result['{DAV:}group-member-set']->getPrincipals();
            }
        }
    }
    
    public function clearCurrentUserData()
    {
        $this->currentUserPrincipal = '';
        $this->calendarHomeSet = '';
    }
    
    /**
     * perform calDavRequest
     * 
     * @param string $method
     * @param string $uri
     * @param strubg $body
     * @param number $depth
     * @param number $tries
     * @param number $sleep
     * @throws Tinebase_Exception
     */
    public function calDavRequest($method, $uri, $body, $depth = 0, $tries = 10, $sleep = 30)
    {
        $response = null;
        if ($this->_requestTries !== null) {
            // overwrite default retry behavior
            $tries = $this->_requestTries;
        }
        while ($tries > 0)
        {
            try {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Sending ' . $method . ' request for uri ' . $uri . ' ...');
                $response = $this->request($method, $uri, $body, array(
                    'Depth' => $depth,
                    'Content-Type' => 'text/xml',
                ));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' Caldav request failed: '
                            . '(' . $this->userName . ')' . $method . ' ' . $uri . "\n" . $body
                            . "\n" . $e->getMessage());
                if (--$tries > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Sleeping ' . $sleep . ' seconds and retrying ... ');
                    sleep($sleep);
                }
                continue;
            }
            break;
        }
        
        if (! $response) {
            throw new Tinebase_Exception("no response");
        }
        
        $result = $this->parseMultiStatus($response['body']);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Uri: ' . $uri . ' | request: ' . $body . ' | response: ' . print_r($response, true));
        
        // If depth was 0, we only return the top item
        if ($depth===0) {
            reset($result);
            $result = current($result);
            $result = isset($result[200])?$result[200]:array();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Result (depth 0): ' . var_export($result, true));
            
            return $result;
        }
        
        $newResult = array();
        foreach($result as $href => $statusList)
        {
            $newResult[$href] = isset($statusList[200])?$statusList[200]:array();
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Result: ' . var_export($newResult, true));
        
        return $newResult;
    }
}
