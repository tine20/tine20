<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Gabriel Malheiros <gabriel.malheiros@serpro.gov.br>
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * SOGO Integrator plugin
 *
 * This plugin provides functionality added by SOGO Integrator
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */

class Tinebase_WebDav_Plugin_Inverse extends Sabre\DAV\ServerPlugin {

    const NS_INVERSE = 'urn:inverse:params:xml:ns:inverse-dav';
 
    const NS_DAV = 'DAV:';

    /**
     * Reference to server object 
     * 
     * @var Sabre\DAV\Server 
     */
    private $server;

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually 
     * implement them 
     * 
     * @param string $uri
     * @return array 
     */
    public function getSupportedReportSet($uri) 
    {
        return array(
            '{' . self::NS_DAV . '}principal-match',
            '{' . self::NS_INVERSE . '}acl-query',
            '{' . self::NS_INVERSE . '}user-query',
        );
    }

    /**
     * Initializes the plugin 
     * 
     * @param Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(Sabre\DAV\Server $server) 
    {
        $this->server = $server;
        
        $server->subscribeEvent('unknownMethod', array($this, 'unknownMethod'));
        $server->subscribeEvent('report',        array($this, 'report'));

        $server->xmlNamespaces[Sabre\CalDAV\Plugin::NS_CALDAV]         = 'cal';
        $server->xmlNamespaces[Sabre\CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';

        $server->resourceTypeMapping['Sabre\CalDAV\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';

        /*array_push($server->protectedProperties,

            '{' . self::NS_CALDAV . '}supported-calendar-component-set',
            '{' . self::NS_CALDAV . '}supported-calendar-data',
            '{' . self::NS_CALDAV . '}max-resource-size',
            '{' . self::NS_CALDAV . '}min-date-time',
            '{' . self::NS_CALDAV . '}max-date-time',
            '{' . self::NS_CALDAV . '}max-instances',
            '{' . self::NS_CALDAV . '}max-attendees-per-instance',
            '{' . self::NS_CALDAV . '}calendar-home-set',
            '{' . self::NS_CALDAV . '}supported-collation-set',
            '{' . self::NS_CALDAV . '}calendar-data',

            // scheduling extension
            '{' . self::NS_CALDAV . '}calendar-user-address-set',

            // CalendarServer extensions
            '{' . self::NS_CALENDARSERVER . '}getctag',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for'

        );*/
    }

    /**
     * This functions handles REPORT requests specific to SOGO Integrator 
     * 
     * @param  string   $reportName 
     * @param  DOMNode  $dom
     * @param  string   $uri
     * @return bool
     */
    public function report($reportName, $dom, $uri) 
    {
        switch($reportName) { 
            case '{' . self::NS_DAV . '}principal-match' :
                $this->principalMatch($dom);
                
                return false;
                
            case '{' . self::NS_INVERSE . '}acl-query' :
                $this->aclQueryReport($dom, $uri);
                
                return false;
                
            case '{' . self::NS_INVERSE . '}user-query' :
                $this->userQuery($dom);
                
                return false;
        }
    }

    /**
     * This function handles support for the POST method
     *
     * @param  string  $method
     * @param  string  $uri
     * @return bool
     */
    public function unknownMethod($method, $uri) 
    {
        switch ($method) {
            case 'POST' :
                $body = $this->server->httpRequest->getBody(true);
                try {
                    $dom = \Sabre\DAV\XMLUtil::loadDOMDocument($body);
                } catch (\Sabre\DAV\Exception\BadRequest $sdavebr) {
                    return;
                }
                
                $reportName = \Sabre\DAV\XMLUtil::toClarkNotation($dom->firstChild);

                switch($reportName) { 
                    case '{' . self::NS_INVERSE . '}acl-query' :
                        $this->aclQueryPost($dom, $uri);
                        
                        return false;
                }
        }
    }
    
    /**
     * resolve contactId to Addressbook_Model_Contact model
     * 
     * @param  string $contactId
     * @throws \Sabre\DAV\Exception\NotFound
     * @return Addressbook_Model_Contact
     */
    protected function _resolveContactId($contactId)
    {
        $filter = new Addressbook_Model_ContactFilter(array (
            array (
                'field'    => 'id',
                'operator' => 'equals',
                'value'    => $contactId,
            ),
            array (
                'field'    => 'type',
                'operator' => 'equals',
                'value'    => 'user',
            )
        ));
        
        $contact = Addressbook_Controller_Contact::getInstance()
            ->search($filter)
            ->getFirstRecord();
        
        if (! $contact instanceof Addressbook_Model_Contact) {
            throw new \Sabre\DAV\Exception\NotFound("user $contactId not found");
        } 
        
        return $contact;
    }
    
    /**
     * handle acl-query post requests
     * 
     * @param  DOMDocument  $dom
     * @param  string       $uri
     */
    public function aclQueryPost(DOMDocument $dom, $uri)
    {
        list($parent, $containerId) = Sabre\DAV\URLUtil::splitPath($uri);
        
        // handle add-user
        $adduser = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'add-user');
        
        if ($adduser->length == 1) {
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId);

            $contact = $this->_resolveContactId($adduser->item(0)->getAttribute('user'));
            
            $newGrant = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                array(
                    'account_id'                           => $contact->account_id,
                    'account_type'                         => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    Tinebase_Model_Grants::GRANT_FREEBUSY  => true
                )
            ));
            
            $grants->merge($newGrant);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' new grants: ' . print_r($grants->toArray(), true));

            try {
                Tinebase_Container::getInstance()->setGrants($containerId, $grants);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                throw new \Sabre\DAV\Exception\Forbidden($tead->getMessage());
            }
            
            $this->server->httpResponse->sendStatus(201);
            $this->server->httpResponse->setHeader('Content-Type','text/xml; charset=utf-8');
        }
        
        // handle remove-user
        $removeuser = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'remove-user');
        
        if ($removeuser->length == 1) {
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId);

            $contact = $this->_resolveContactId($removeuser->item(0)->getAttribute('user'));
            
            $newGrant = $grants->filter('account_id', $contact->account_id);
            $grants->removeRecords($newGrant);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' id container : ' . print_r($newGrant, true));

            try {
                Tinebase_Container::getInstance()->setGrants($containerId, $grants);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                throw new \Sabre\DAV\Exception\Forbidden($tead->getMessage());
            }
            
            $this->server->httpResponse->sendStatus(201);
            $this->server->httpResponse->setHeader('Content-Type', 'text/xml; charset=utf-8');
        }
        
        // handle set-roles
        $setroles = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'set-roles');
        
        if ($setroles->length == 1) {
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId);

            $contact = $this->_resolveContactId($setroles->item(0)->getAttribute('user'));
            
            $newGrant = $grants->filter('account_id', $contact->account_id);
            $grants->removeRecords($newGrant);

            $check = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'ObjectCreator');
            
            if($check->length == 1) {
                $newGrant->readGrant   = true;
                $newGrant->addGrant    = true;
                $newGrant->editGrant   = true;
                $newGrant->exportGrant = true;
                $newGrant->syncGrant   = true;
            } else {
                $newGrant->readGrant   = false;
                $newGrant->addGrant    = false;
                $newGrant->editGrant   = false;
                $newGrant->exportGrant = false;
                $newGrant->syncGrant   = false;

            }
            
            $check = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'ObjectEditor');
            if ($check->length == 1) {
               $newGrant->editGrant = true;
            } else {
               $newGrant->editGrant = false;
            }

            $check = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'ObjectViewer');
            if ($check->length == 1) {
                $newGrant->readGrant = true;
            } else {
                $newGrant->readGrant = false;
            }

            $check = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'ObjectEraser');
            if ($check->length == 1) {
               $newGrant->deleteGrant = true;
            } else {
               $newGrant->deleteGrant = false;
            }

            $grants->merge($newGrant);

            try {
                Tinebase_Container::getInstance()->setGrants($containerId, $grants);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                throw new \Sabre\DAV\Exception\Forbidden($tead->getMessage());
            }
            
            $this->server->httpResponse->sendStatus(201);
            $this->server->httpResponse->setHeader('Content-Type', 'text/xml; charset=utf-8');
        }
        
    }
    
    /**
     * ACE reports for sogo integrator
     * 
     * @param DOMDocument $dom
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function aclQueryReport(DOMDocument $dom, $uri)
    {
        list($parent, $containerId) = Sabre\DAV\URLUtil::splitPath($uri);
        
        // handle user-list
        $userlists = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'user-list');
        
        if ($userlists->length == 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' id container : ' . $containerId );
            
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($containerId);
            
            $domout = new DOMDocument('1.0','utf-8');
            $domout->formatOutput = true;
            
            $userlist = $domout->createElement('user-list');
            $domout->appendChild($userlist);
            
            foreach($grants as &$value) {
                switch($value['account_type']) {
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                        try {
                            $account = Tinebase_User::getInstance()->getFullUserById($value['account_id']);
                        } catch (Tinebase_Exception_NotFound $e) {
                            $account = Tinebase_User::getInstance()->getNonExistentUser();
                        }
                        
                        $userElement = $domout->createElement('user');
                        
                        $userElement->appendChild(new DOMElement('id',          $account->contact_id));
                        $userElement->appendChild(new DOMElement('displayName', $account->accountFullName));
                        $userElement->appendChild(new DOMElement('email',       $account->accountEmailAddress));
                        
                        $userlist->appendChild($userElement);
                        
                        break;
                        
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                        try {
                            $group = Tinebase_Group::getInstance()->getGroupById($value['account_id']);
                        } catch (Tinebase_Exception_Record_NotDefined $e) {
                            $group = Tinebase_Group::getInstance()->getNonExistentGroup();
                        }
                        
                        $group = $group->toArray();
                        
                        // @todo add xml for group
                        
                        break;
                        
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                        $userElement = $domout->createElement('user');
                        
                        // @todo this is not an anonymous user, but all authenticated users
                        
                        $userElement->appendChild(new DOMElement('id',          'anonymous'));
                        $userElement->appendChild(new DOMElement('displayName', 'Public User'));
                        $userElement->appendChild(new DOMElement('email',       'anonymous'));
                        
                        $userlist->appendChild($userElement);
                        
                        break;
                        
                    default:
                        throw new Tinebase_Exception_InvalidArgument('Unsupported accountType.');
                        
                        break;
                }
            }
        
            $xmml = $domout->saveXML();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' id container : ' . str_replace(array("\r\n", "\n", "\r","  "),'',$xmml));
            
            $this->server->httpResponse->sendStatus(207);
            $this->server->httpResponse->setHeader('Content-Type','text/xml; charset=utf-8');
            $this->server->httpResponse->sendBody(str_replace(array("\r\n", "\n", "\r","  "),'',$xmml));
        }
        
        // handle roles
        $roles = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'roles');
        
        if ($roles->length == 1) {

            $contact = $this->_resolveContactId($roles->item(0)->getAttribute('user'));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact id : ' . $contact->getId());
            
            $acls = Tinebase_Container::getInstance()->getGrantsOfAccount($contact->account_id, $containerId);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' id container : ' . print_r($acls, true));
            
            $domout = new DOMDocument('1.0', 'utf-8');
            $domout->formatOutput = true;
            
            $role = $domout->createElement('roles');
            $domout->appendChild($role);
            
            foreach ($acls as $acl => $value) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' fazendo acl : ' . $acl );
 
                switch ($acl) {
                    case Tinebase_Model_Grants::GRANT_READ:
                        if ($value) {
                            $role->appendChild(new DOMElement('ObjectViewer'));
                        }
                        
                        break;
                        
                    case Tinebase_Model_Grants::GRANT_PRIVATE:
                        if ($value) {
                            $role->appendChild(new DOMElement('PrivateViewer'));
                        }
                        
                        break;
                        
                    case Tinebase_Model_Grants::GRANT_EDIT:
                        if ($value) {
                            $role->appendChild(new DOMElement('ObjectEditor'));
                        }
                        
                        break;
                        
                    case Tinebase_Model_Grants::GRANT_ADD:
                        if ($value) {
                            $role->appendChild(new DOMElement('ObjectCreator'));
                        }
                        
                        break;
                        
                    case Tinebase_Model_Grants::GRANT_DELETE:
                        if ($value) {
                            $role->appendChild(new DOMElement('ObjectEraser'));
                        }
                        
                        break;
                }
            }
 
            $xmml = $domout->saveXML();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' id container : ' . str_replace(array("\r\n", "\n", "\r","  "),'',$xmml));
            
            $this->server->httpResponse->sendStatus(207);
            $this->server->httpResponse->setHeader('Content-Type', 'text/xml; charset=utf-8');
            $this->server->httpResponse->sendBody(str_replace(array("\r\n", "\n", "\r","  "), '', $xmml));
        }
    }
    
    /**
     * Implementing Principal Match
     * 
     * @param DOMDocument $dom
     */
    public function principalMatch(DOMDocument $dom)
    {
        $xml = array(
            array(
                'href' => 'principals/users/' . Tinebase_Core::getUser()->contact_id
            )
        );
        
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($xml));
    }

    /**
     * 
     * @param DOMDocument $dom
     */
    public function userQuery(DOMDocument $dom) 
    {
        $users = $dom->getElementsByTagNameNS(self::NS_INVERSE, 'users');
        
        if ($users->length == 1) {
            $match = $users->item(0)->getAttribute('match-name');
            
            $filter = new Addressbook_Model_ContactFilter(array (
                array (
                    'field'    => 'query',
                    'operator' => 'contains',
                    'value'    => $match,
                ),
                array (
                    'field'    => 'type',
                    'operator' => 'equals',
                    'value'    => 'user',
                )
            ));
            
            $records = Addressbook_Controller_Contact::getInstance()->search($filter);
            
            $domout = new DOMDocument('1.0','utf-8');
            $domout->formatOutput = true;
            
            $users = $domout->createElement('users');
            $domout->appendChild($users);
            
            foreach( $records as $record) {
                $user = $domout->createElement('user');
                
                $user->appendChild(new DOMElement('id',          $record->id));
                $user->appendChild(new DOMElement('displayName', $record->n_fileas));
                $user->appendChild(new DOMElement('email',       $record->email));
                
                $users->appendChild($user);
            }

            $xmml = $domout->saveXML();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' id container : ' . str_replace(array("\r\n", "\n", "\r","  "),'',$xmml));
            
            $this->server->httpResponse->sendStatus(207);
            $this->server->httpResponse->setHeader('Content-Type','text/xml; charset=utf-8');
            $this->server->httpResponse->sendBody(str_replace(array("\r\n", "\n", "\r","  "),'',$xmml));
        }
    }
}
