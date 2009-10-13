<?php
/**
 * Rest backend for RequestTracker
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Rest backend for RequestTracker
 * 
 * NOTE: the foreing "REST" server isn't a real rest server, so we use a bare http client
 *       @see {http://www.gossamer-threads.com/lists/rt/devel/71088}
 * 
 * @see         {http://wiki.bestpractical.com/view/REST}
 * @package     RequestTracker
 */
class RequestTracker_Backend_Rest //implements Tinebase_Backend_Interface 
{
    /**
     * config for this backend
     *
     * @var Zend_Config
     */
    protected $_config = NULL;
    
    /**
     * http client
     *
     * @var Zend_Http_Client
     */
    protected $_httpClient = NULL;
    
    /**
     * search count cache
     *
     * @var int
     */
    protected $_searchCountCache = 0;
    
    public function __construct()
    {
        $this->_config = isset(Tinebase_Core::getConfig()->requesttracker) ? Tinebase_Core::getConfig()->requesttracker : new Zend_Config(array());
        $this->_connect();
    }
    
    /**
     * check available queues
     * 
     * @return array of queueNames
     */
    public function getQueues()
    {
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = 'RequestTrackerGetQueues' . Tinebase_Core::getUser()->getId();
        $availableQueues = $cache->load($cacheId);
        
        if (!$availableQueues) {
            $availableQueues = array();
            foreach ($this->_config->rest->queues as $queueName) {
                if ($this->_checkQueue($queueName)) {
                    $availableQueues[] = $queueName;
                }
            }
            
            $cache->save($availableQueues, $cacheId, array('rt'));
        }
        
        return $availableQueues;
    }
    
    /**
     * checks if queue is available for current user
     *
     * @param  string $_queueName
     * @return bool
     */
    protected function _checkQueue($_queueName)
    {
        $this->_httpClient->resetParameters();
        $this->_httpClient->setUri($this->_config->rest->url . "/REST/1.0/queue/$_queueName/show");
        $this->_httpClient->setMethod(Zend_Http_Client::GET);
        
        $response = $this->_httpClient->request();
        
        return (preg_match('/^Name: .+/m', $response->getBody()));
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination         $_pagination
     * @param  boolean                           $_onlyIds
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        $this->_httpClient->resetParameters();
        $this->_httpClient->setUri($this->_config->rest->url . "/REST/1.0/search/ticket/");
        $this->_httpClient->setMethod(Zend_Http_Client::GET);
        
        $this->_appendFilter($_filter);
        $this->_appendPagination($_pagination);
        
        $this->_httpClient->setParameterGet('format', 'l');
        Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' about to query ' . $this->_config->rest->url . "/REST/1.0/search/ticket/");
        $response = $this->_httpClient->request();
        
        Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' request :' . $this->_httpClient->getLastRequest());
        //Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' response :' . $response->asString());
        
        $tickets = $this->_rawDataToRecords($response->getBody());
        $this->_searchCountCache = count($tickets);
        
        return $_onlyIds ? $tickets->getId() : $tickets;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter) {
        return $this->_searchCountCache;
    }
    
    /**
     * Return a single record
     *
     * @param string $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        // get basics
        $this->_httpClient->resetParameters();
        $this->_httpClient->setUri($this->_config->rest->url . "/REST/1.0/ticket/". $_id . "/show");
        $this->_httpClient->setMethod(Zend_Http_Client::GET);
        $response = $this->_httpClient->request();
        $ticket = $this->_rawDataToTicket($response->getBody());
        
        // get history
        $this->_httpClient->resetParameters();
        $this->_httpClient->setUri($this->_config->rest->url . "/REST/1.0/ticket/". $_id . "/history");
        $this->_httpClient->setParameterGet('format', 'l');
        $this->_httpClient->setMethod(Zend_Http_Client::GET);
        $response = $this->_httpClient->request();
        
        Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' request :' . $this->_httpClient->getLastRequest());
        //Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' response :' . $response->asString());
        
        $ticket->History = $this->_rawDataToHistory($response->getBody());
        
        return $ticket;        
    }
    
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_RecordSet of Tinebase_Record_Interface
     */
    public function getMultiple($_ids, $_containerIds = NULL)
    {
        $tickets = new Tinebase_Record_RecordSet('RequestTracker_Model_Ticket');
        foreach ((array)$_ids as $id) {
            $tickets->addRecord($this->get($id));
        }
        return $tickets;
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        $filter = new RequestTracker_Model_TicketFilter(array());
        $pagination = new Tinebase_Model_Pagination(array(
            'order'      => $_orderBy,
            'direction'  => $_orderDirection
        ));
        
        return $this->search($filter, $pagination);
    }
    
    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     */
    //public function create(Tinebase_Record_Interface $_record);
    
    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_contact
     * @return Tinebase_Record_Interface|NULL
     */
    //public function update(Tinebase_Record_Interface $_record);
    
    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     */
    //public function updateMultiple($_ids, $_data);
        
    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return void
     */
    //public function delete($_identifier);
    
    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return 'REST';
    }
    
    /**
     * converts multiple raw tickets into a record set of ticket records
     *
     * @param  string $rawData
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecords($_rawData)
    {
        $tickets = new Tinebase_Record_RecordSet('RequestTracker_Model_Ticket');
        
        foreach (explode("\n--\n", $_rawData) as $rawTicket) {
            $ticket = $this->_rawDataToTicket($rawTicket);
            if ($ticket->getId()) {
                $tickets->addRecord($ticket);
            }
        }
        
        return $tickets;
    }
    
    /**
     * converts a raw ticket into a record
     *
     * @param  string $_rawData
     * @return RequestTracker_Model_Ticket
     */
    protected function _rawDataToTicket($_rawData)
    {
        $dataArray = array();
        
        $matches = array();
        foreach (explode("\n", $_rawData) as $line) {
            if (preg_match("/^([a-zA-Z]{2,}): (.+)/", $line, $matches)) {
                if (count($matches) == 3) {
                    list($all, $key, $value) = $matches;
                    $dataArray[$key] = $value;
                }
            }
        }
        
        foreach ($dataArray as $property => $value) {
        	switch ($property) {
        	    case 'id':
        	        $dataArray[$property] = substr($value, 7);
        	        break;
        	    case 'Created':
                case 'Starts':
                case 'Started':
                case 'Due':
                case 'Resolved':
                case 'Told':
                case 'LastUpdated':
                    $dataArray[$property] = $value == 'Not set' ? NULL : new Zend_Date($value, 'EEE MMM dd HH:mm:ss yyyy');
                    break;
        	}
        }
        
        return new RequestTracker_Model_Ticket($dataArray);
    }
    
    /**
     * converts raw history data into a set of history items
     *
     * @param  string $_rawData
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToHistory($_rawData) {
        $history = new Tinebase_Record_RecordSet('RequestTracker_Model_TicketHistoryItem');
        
        foreach (explode("\n--\n", $_rawData) as $rawHistory) {
            $dataArray = array();
            
            $matches = array();
            foreach (explode("\n", $rawHistory) as $line) {
                if (preg_match("/^([a-zA-Z]{2,}): (.+)/m", $line, $matches)) {
                    if (count($matches) == 3) {
                        list($all, $key, $value) = $matches;
                        $dataArray[$key] = $value;
                    }
                }
            }
            
            if (preg_match("/^\nContent: (.*)\nCreator:/sm", $rawHistory, $matches)) {
                $dataArray['Content'] = $matches[1];
            }
            
            //Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' History Type :' . $dataArray['Type']);
            $history->addRecord(new RequestTracker_Model_TicketHistoryItem($dataArray));
        }
        
        return $history;
    }
    
    /**
     * appends filter to request
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @return void
     */
    protected function _appendFilter($_filter, $_condition = ' AND ') {
        if ($_filter) {
            $parms = array();
            foreach ($_filter->getFilterObjects() as $filterObject) {
                switch ($filterObject->getOperator()) {
                    case 'equals':      $op='=';    break;
                    case 'contains':    $op='LIKE'; break;
                    case 'not':         $op='!=';   break;
                    case 'greater':     $op='>';    break;
                    case 'less':        $op='<';    break;
                }
                
                $field = $filterObject->getField();
                $value = $filterObject->getValue();
                
                switch ($field) {
                    case 'query':
                        $parms[] = "(id = '$value' OR subject LIKE '$value')";
                        break;
                    case 'status':
                        if ($op !== '=') {
                            $idx = array_search($value, RequestTracker_Model_Ticket::$status);
                            if ($op == '>') {
                                $status = array_slice(RequestTracker_Model_Ticket::$status, 0, $idx+1);
                            } else {
                                $status = array_slice(RequestTracker_Model_Ticket::$status, $idx, count(RequestTracker_Model_Ticket::$status));
                            }
                            Tinebase_Core::getLogger()->debug(__FILE__ . '::' . __LINE__ . ' filter for status :' . print_r($status, true));
                            $parms[] = "(status = '" .implode("' OR status = '", $status) . "')";
                            break;
                        }
                        // fall through for '='
                    default:
                        $parms[] = "$field $op '$value'";
                        break;
                }
            }
            
            $this->_httpClient->setParameterGet('query', implode($_condition, $parms));
        }
    }
    
    /**
     * appends pagination
     *
     * @param  Tinebase_Model_Pagination $_pagination
     * @return void
     */
    protected function _appendPagination($_pagination)
    {
        if ($_pagination) {
            $this->_httpClient->setParameterGet(array(
                'orderby' => $_pagination->sort
            ));
        }
    }
    
    /**
     * connects to request tracker
     *
     * @return void
     */
    protected function _connect()
    {
        if (! $this->_config->rest || $this->_config->rest->url) {
            throw new Tinebase_Exception_NotFound('Could not connect to RequestTracker: No REST url given!');
        }
        
        $config = array(
            'url'       => $this->_config->rest->url,
            'useragent' => 'Tine 2.0 remote client (rv: 0.2)',
            'keepalive' => true
        );
        
        $this->_httpClient = new Zend_Http_Client($this->_config->rest->url, $config);
        $this->_httpClient->setCookieJar();
        
        // login
        $this->_httpClient->setMethod(Zend_Http_Client::POST);
        $this->_httpClient->setUri($this->_config->rest->url . "/REST/1.0/ticket/");
        
        $loginName = Tinebase_Core::getUser()->accountLoginName;
        if ($this->_config->useCustomCredendials) {
            $this->_httpClient->setAuth($this->_config->customCredentials->$loginName->username, $this->_config->customCredentials->$loginName->password);
        } else {
            $credentialCache = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
            Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($credentialCache);
            
            $this->_httpClient->setAuth(Tinebase_Core::getUser()->accountLoginName, $credentialCache->password);
        }
        
        $response = $this->_httpClient->request();
        
        if ($response->getStatus() != 200) {
            throw new Tinebase_Exception_Record_NotAllowed($response->getMessage(), $response->getStatus());
        }
    }
}