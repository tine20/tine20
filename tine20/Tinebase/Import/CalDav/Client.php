<?php

/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        
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
    
    public function findCurrentUserPrincipal()
    {
        $result = $this->calDavRequest('PROPFIND', '/principals/', self::findCurrentUserPrincipalRequest);
        if (isset($result['{DAV:}current-user-principal']))
        {
            try {
                $user = Tinebase_User::getInstance()->getUserByLoginName($this->userName);
                Tinebase_Core::set(Tinebase_Core::USER, $user);
                $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($this->userName, $this->password);
                Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
            } catch (Tinebase_Exception_NotFound $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t find tine20 user: ' . $this->userName);
                return false;
            }
            $this->currentUserPrincipal = $result['{DAV:}current-user-principal'];
            $this->principals[$this->currentUserPrincipal] = $user;
            return true;
        }
        
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find current users principal');
        return false;
    }
    
    public function findCurrentUserPrincipalForUsers(array $users)
    {
        $result = true;
        foreach ($users as $username => $pwd) {
            $this->userName = $username;
            $this->password = $pwd;
            if (!$this->findCurrentUserPrincipal()) {
                $result = false;
            }
        }
        return $result;
    }
    
    public function findCalendarHomeSet()
    {
        if ('' == $this->currentUserPrincipal && ! $this->findCurrentUserPrincipal())
            return false;
        $result = $this->calDavRequest('PROPFIND', $this->currentUserPrincipal, self::findCalendarHomeSetRequest);
        if (isset($result['{urn:ietf:params:xml:ns:caldav}calendar-home-set']))
        {
            $this->calendarHomeSet = $result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'];
            return true;
        }
        
        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find calendar homeset');
        return false;
    }
    
    public function resolvePrincipals(array $privileges)
    {
        foreach ($privileges as $ace)
        {
            if ( $ace['principal'] == '{DAV:}authenticated' || $ace['principal'] == $this->currentUserPrincipal ||
                 isset($this->principals[$ace['principal']]) || isset($this->principalGroups[$ace['principal']]))
                         continue;
            $result = $this->calDavRequest('PROPFIND', $ace['principal'], self::resolvePrincipalRequest);
            if (isset($result['{DAV:}group-member-set'])) {
                $this->principalGroups[$ace['principal']] = $result['{DAV:}group-member-set']->getPrincipals();
            }
        }
    }
    
    public function clearCurrentUserData()
    {
        $this->currentUserPrincipal = '';
        $this->calendarHomeSet = '';
    }
    
    public function calDavRequest($method, $uri, $body, $depth = 0)
    {
        $redo = 0;
        while (++$redo < 4)
        {
            try {
                $response = $this->request($method, $uri, $body, array(
                    'Depth' => $depth,
                    'Content-Type' => 'text/xml',
                ));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' caldav request failed, sleeping 60 seconds and retrying: '
                            . $method . ' ' . $uri . "\n" . $body . "\n" . $e->getMessage());
                sleep(60);
                continue;
            }
            break;
        }
        
        $result = $this->parseMultiStatus($response['body']);
        
        //fputs($this->requestLogFH, $method.' '.$uri."\n".$body."\n".$depth."\n".$response['body']."\n\n\n\n\n\n\n", 10000000);
        //echo $body."\n\n";
        //print_r($response);
        
        // If depth was 0, we only return the top item
        if ($depth===0) {
            reset($result);
            $result = current($result);
            return isset($result[200])?$result[200]:array();
        }
        
        $newResult = array();
        foreach($result as $href => $statusList)
        {
            $newResult[$href] = isset($statusList[200])?$statusList[200]:array();
        }
        
        return $newResult;
    }
}