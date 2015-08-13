<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cassiano Dal Pizzol <cassiano.dalpizzol@serpro.gov.br>
 * @author      Bruno Costa Vieira <bruno.vieira-costa@serpro.gov.br>
 * @author      Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 *
 * @todo organize the folderMap Code, put into a singleton class to use it globally????
 */

class Expressomail_Backend_Message //extends Tinebase_Backend_Sql_Abstract
{

    const MAXSEARCHRESULTS = 1000;
    const IMAPDELIMITER = '/';

    protected $_appName = 'Expressomail';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Expressomail_Model_Message';

//    /**
//    * default column(s) for count
//    *
//    * @var string
//    */
//    protected $_defaultCountCol = 'id';

    protected $_imapSortParams = array('subject','from_name','from_email','to','size','received','sent');

    protected $_accountMap = array();
    protected $_folderMap = array();

//    /**
//     * foreign tables (key => tablename)
//     *
//     * @var array
//     */
//    protected $_foreignTables = array(
//        'to'    => array(
//            'table'     => 'expressomail_cache_message_to',
//            'joinOn'    => 'message_id',
//            'field'     => 'email',
//            'preserve'  => TRUE,
//        ),
//        'cc'    => array(
//            'table'  => 'expressomail_cache_message_cc',
//            'joinOn' => 'message_id',
//            'field'  => 'email',
//            'preserve'  => TRUE,
//        ),
//        'bcc'    => array(
//            'table'  => 'expressomail_cache_message_bcc',
//            'joinOn' => 'message_id',
//            'field'  => 'email',
//            'preserve'  => TRUE,
//        ),
//        'flags'    => array(
//            'table'         => 'expressomail_cache_message_flag',
//            'joinOn'        => 'message_id',
//            'field'         => 'flag',
//            'preserve'  => TRUE,
//        ),
//    );

//    /**
//     * find existance of values recursivelly on array
//     * @param array $array
//     * @param type $search
//     * @param type $mode
//     * @return boolean
//     *
//     * @todo implement it as a static method on a helper class (Tinebase_Helper)
//     */
//    protected function _searchNestedArray(array $array, $search, $mode = 'value') {
//
//        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($array)) as $value) {
//            if ($search === ${${"mode"}})
//                return true;
//        }
//        return false;
//    }

    public function getTableName()
    {
        return 'expressomail_cache_message';
    }

    protected function _getAllFolders()
    {
        $return = array();
        $accounts = Expressomail_Controller_Account::getInstance()->search()->getArrayOfIds();
        $controllerFolder = Expressomail_Controller_Folder::getInstance();
        foreach ($accounts as $account)
        {
            $return = array_merge($return, $controllerFolder->getFoldersAS($account));
        }

        return $return;
    }

    /**
     *
     * @param type $paths
     * @return type
     */
    protected function _getFoldersInfo($paths)
    {
        $return = array();
        foreach ($paths as $tmp)
        {
            $tmp = is_array($tmp) ? explode(self::IMAPDELIMITER, $tmp['path']) : explode(self::IMAPDELIMITER, $tmp);

            if (empty($tmp[0]))
            {
                array_shift($tmp);
            }

            $userId = array_shift($tmp);
            $folderId = array_pop($tmp);
            $folder = Expressomail_Backend_Folder::decodeFolderUid($folderId);

            $return[$folderId] = array($userId, Expressomail_Model_Folder::decodeFolderName($folder['globalName']));
        }

        return $return;
    }

    /**
     * get all folders globalname and accountId
     *
     * @param array $_pathFilters
     * @return array
     *
     * @todo implement not in
     * @todo what happens when path is empty???? is the same as /allinboxes???
     */
    protected function _processPathFilters($_pathFilters)
    {
        $paths = array();
        foreach ($_pathFilters as $pathFilter){
            if (empty($pathFilter['value'])) { // get allfolders from all accounts
                $pathFilter['value'] = $this->_getAllFolders();
            } else if ($pathFilter['value'] ===  Expressomail_Model_MessageFilter::PATH_ALLINBOXES
                    || (is_array($pathFilter['value']) && 
                        (array_search(Expressomail_Model_MessageFilter::PATH_ALLINBOXES, $pathFilter['value']) !== false))) { // get all INBOX from all accounts
                $pathFilter['value'] = Expressomail_Controller_Folder::getInstance()->getAllInboxes();
            } else if (($pathFilter['operator'] === 'notin')) {
                $pathFilter['value'] = array_diff($this->_getAllFolders(), $pathFilter['value']);
            }
            if (is_array($pathFilter['value'])){
                $paths = array_merge($paths,  $pathFilter['value']);
            }
        }

        return $this->_getFoldersInfo($paths);
    }

    /**
     * Generate all the necessary imap filters to be processed by the search method
     *
     * @param mixed $_filter
     * @param Tinebase_Model_Pagination $_pagination
     *
     * @todo implement filters: flags, account_id, id, received, messageuid
     * @todo verify results with empty value
     */
    protected function _generateImapSearch($_filter, Tinebase_Model_Pagination $_pagination = NULL)
    {
        $return = array();

        if (!is_array($_filter))
        {
            $filters = array();
            $filters[] = $_filter;
        }
        else
        {
            $filters = $_filter;
        }

        foreach ($filters as $filter)
        {
            if (!is_a($filter, 'Tinebase_Model_Filter_Abstract'))
            {
                $value = $filter['value'];
                $field = $filter['field'];
                $operator = $filter['operator'];
            }
            else
            {
                $value = $filter->getValue();
                $field = $filter->getField();
                $operator = $filter->getOperator();
            }
            $escapedValue = '"'.$value.'"';

//            if (!empty($value))
//            {
                switch ($field)
                {
                    case 'query' :
                        if (!empty($value))
                        {
                            // make it compatible with ExpressoMail
                            
                            $return[] = "OR OR OR SUBJECT $escapedValue FROM $escapedValue CC $escapedValue BODY $escapedValue";
                        }
                        break;
                    case 'subject' :
                        if (!empty($value))
                        {
                            $return[] = "SUBJECT $escapedValue";
                        }
                        break;
                    case 'body' :
                        if (!empty($value))
                        {
                            $return[] = "BODY $escapedValue";
                        }
                        break;
                    case 'from_name' : // we can't diferentiate with imap filters
                    case 'from_email' :
                        if (!empty($value))
                        {
                            $return[] = "FROM $escapedValue";
                        }
                        break;
                    case 'to' :
                        if (!empty($value))
                        {
                            $return[] = "TO $escapedValue";
                        }
                        break;
                    case 'cc' :
                        if (!empty($value))
                        {
                            $return[] = "CC $escapedValue";
                        }
                        break;
                    case 'bcc' :
                        if (!empty($value))
                        {
                            $return[] = "BCC $escapedValue";
                        }
                        break;
                    case 'received' :  // => array('filter' => 'Tinebase_Model_Filter_DateTime'),
                        if (!empty($value))
                        {
                            $return[] = $filter->getFilterImap();
                        }
                        break;
                    case 'flags' :
                        $func = function($flag)
                        {
                            switch ($flag)
                            {
                            	case 'Passed'    : return 'UNKEYWORD Passed';
                                case '\Answered'    : return 'UNANSWERED';
                                case '\Flagged'     : return 'UNFLAGGED';
                                case '\Seen'        : return 'UNSEEN';
                                case '\Draft'       : return 'UNDRAFT';
                                case '\Deleted'     : return 'UNDELETED';
                            }
                        };
                        $value = array_map($func, (array)$value);

                        switch ($operator)
                        {
                            case 'in' :
                                $return[] = empty($value) ? 'UNSEEN' : 'NOT (' . implode(' ',$value) . ')';
                                break;
                            case 'notin' :
                                $return[] = empty($value) ? 'UNSEEN' : implode(' ',$value);
                                break;
                        }
                        break;
                }
//            }
        }
        return $return;

//        $filters = $_filterArray['filters'];
//        Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message Search = $_pagination' . print_r($_pagination,true));

    }

    /**
     * Get filter model and pagination model and parse the rules
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return string
     *
     * @todo exclude $_pagination from this method
     */
    protected function _parseFilterGroup(Tinebase_Model_Filter_FilterGroup $_filter = NULL,
                                            Tinebase_Model_Pagination $_pagination = NULL)
    {
        $return = array();
        $return['filters'] = array();
        $return['paths'] = array();

        $return['paths'] = array_merge($return['paths'],
            (array)$this->_processPathFilters($_filter->getFilter('path', true)));
        foreach (array('to', 'cc', 'bcc', 'flags') as $field)
        {
            $return['filters'] = array_merge($return['filters'],
                (array)$this->_generateImapSearch($_filter->getFilter($field, true), $_pagination));
        }

        foreach ($_filter->getFilterObjects() as $filter)
        {
            if ($filter instanceof Tinebase_Model_Filter_FilterGroup)
            {
                $return = $this->_parseFilterGroup($filter, $_pagination);
            }
            else if ($filter instanceof Tinebase_Model_Filter_Id)
            {
                if ($filter->getField() == 'folder_id')
                {
                    $apaths = $this->_getAllFolders();
                    $id = $filter->getvalue();

                    if(array_key_exists($id, $apaths))
                    {
                        $return['paths'] = array($id => $apaths[$id]);
                    }
                }
                else
                {
                    $return['filters'] = 'Id';
                }
            }
            else if ($filter instanceof Tinebase_Model_Filter_Abstract)
            {
                $return['filters'] = array_merge($return['filters'],
                        (array)$this->_generateImapSearch($filter, $_pagination));
            }

        }

        return $return;
    }

    /**
     * Create a messageId
     * @param string $_messageId
     * @return string
     */
    static public function createMessageId($_accountId, $_folderId, $_messageUid)
    {
        $messageId = base64_encode($_accountId . ';' . $_folderId . ';' . $_messageUid);
        $count = substr_count($messageId, '=');
        return substr($messageId,0, (strlen($messageId) - $count)) . $count;
    }

    /**
     * Decode the messageId
     * @param string $_messageId
     * @return  array
     */
    static public function decodeMessageId($_messageId)
    {
        $return = array(
            'accountId'     => null,
            'folderId'      => null,
            'messageUid'    => null,
        );
        $decoded = base64_decode(str_pad(substr($_messageId, 0, -1), substr($_messageId, -1), '='));
        if ($decoded){
            $arrParts = explode(';', $decoded);
            if (count($arrParts) > 2 ){
                list($return['accountId'], $return['folderId'], $return['messageUid']) = $arrParts;
            }
        }
        return $return;
    }

    /**
     * create new message for the cache
     *
     * @param array $_message
     * @param Expressomail_Model_Folder $_folder
     * @return Expressomail_Model_Message
     */
    protected function _createModelMessage(array $_message, Expressomail_Model_Folder $_folder = NULL)
    {

        // Optimization!!!
        if ($_folder == NULL)
        {
            if (isset($_message['folder_id']))
            {
                $_folder = array_key_exists($_message['folder_id'], $this->_folderMap) ?
                        $this->_folderMap[$_message['folder_id']] :
                        $this->_folderMap[$_message['folder_id']] =
                            Expressomail_Controller_Folder::getInstance()->get($_message['folder_id']);
            }
            else
            {
                return NULL;
            }

        }

        $message = new Expressomail_Model_Message(array(
            'id'            => self::createMessageId($_folder->account_id, $_folder->getId(), $_message['uid']),
            'account_id'    => $_folder->account_id,
            'messageuid'    => $_message['uid'],
            'folder_id'     => $_folder->getId(),
            'timestamp'     => Tinebase_DateTime::now(),
            'received'      => Expressomail_Message::convertDate($_message['received']),
            'size'          => $_message['size'],
            'flags'         => $_message['flags'],
        ),true);

        $message->parseStructure($_message['structure']);
        $message->parseHeaders($_message['header']);
        $message->fixToListModel();
        $message->parseBodyParts();
        $message->parseSmime($_message['structure']);

        $attachments = Expressomail_Controller_Message::getInstance()->getAttachments($message);
        $message->has_attachment = (count($attachments) > 0) ? true : false;

        if ($message->content_type === Expressomail_Model_Message::CONTENT_TYPE_MULTIPART_REPORT) {
            $bodyParts = $message->getBodyParts($message->structure);
            foreach ($bodyParts as $partId => $partStructure) {
                if ($partStructure['contentType'] === Expressomail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822) {
                    $partHeaders = Expressomail_Controller_Message::getInstance()->getMessageHeaders($message, $partId, true);
                    $message->subject = $partHeaders['subject'] . ' - ' . $message->subject;
                }
            }
        }

        return $message;
    }

    /**
     * create new message for the cache
     *
     * @param array $_message
     * @param Expressomail_Model_Folder $_folder
     * @return Expressomail_Model_Message
     */
    protected function _createModelMessageArray(array $_messages, Expressomail_Model_Folder $_folder = NULL)
    {

        $return = array();
        foreach ($_messages as $uid => $msg)
        {
            $message = $this->_createModelMessage($msg, $_folder);
            $return[$uid] = $message;
        }

        return $return;
    }

    protected function _getImapSortParams(Tinebase_Model_Pagination $_pagination = NULL)
    {
        if ($_pagination != NULL)
        {
          if($_pagination->sort)
           {
            switch ($_pagination->sort)
            {
                case 'subject' :
                case 'to' :
                case 'size' :
                    $value = strtoupper($_pagination->sort);
                    $sort = $_pagination->dir === 'ASC' ?
                        array($value) : array('REVERSE ' . $value);
                    break;
                case 'from_name' :
                case 'from_email' :
                    $sort = $_pagination->dir === 'ASC' ?
                        array('FROM') : array('REVERSE FROM');
                    break;
                case 'received' :
                    $sort = $_pagination->dir === 'ASC' ?
                         array('ARRIVAL') : array('REVERSE ARRIVAL');
                    break;
                case 'sent' :
                    $sort = $_pagination->dir === 'ASC' ?
                        array('DATE') : array('REVERSE DATE');
                    break;
                default :
                    $sort = array('REVERSE ARRIVAL');
            }
            return $sort;
          }
        }

        return array('REVERSE ARRIVAL');
    }
    
    /**
     *
     * @param Expressomail_Backend_ImapProxy $_imap
     * @param type $_ids
     * @param type $_folderId
     * @return type 
     */
    protected function _getSummary(Expressomail_Backend_ImapProxy $_imap, $_ids, $_folderId){
        $return = array();
        $pos = 0;
        $last = count($_ids) - 1;
        do
        {
            $ids = array_slice($_ids, $pos, 1000);
            $return = empty($return) ? $_imap->getSummary($ids, null, null, $_folderId) : 
                array_merge($return, $_imap->getSummary($ids, null, null, $_folderId));
            $pos += count($ids);
        }
        while($pos < $last);
        
        return $return;
    }

    /**
     * Get Ids for filter
     * @param array $imapFilters
     * @param Tinebase_Model_Pagination $_pagination
     * @return array
     *
     * @todo pass the search parameters
     */
    protected function _getIds(array $_imapFilters, Tinebase_Model_Pagination $_pagination = NULL)
    {
        $messages = array();
        if (empty($_imapFilters['paths']))
        {
            $_imapFilters['paths'] = $this->_getAllFolders();
        }
        $sort = $this->_getImapSortParams($_pagination);

        // do a search for each path on $imapFilters
        foreach ($_imapFilters['paths'] as $folderId => $path)
        {
            list($accountId, $mailbox) = $path;

            $imap = Expressomail_Backend_ImapFactory::factory($accountId);
            $imap->selectFolder(Expressomail_Model_Folder::encodeFolderName($mailbox));

            // TODO: pass the search parameter too.
            $messages[$folderId] = $imap->sort((array)$sort, (array)$_imapFilters['filters']);

        }

        return $messages;
    }

    protected function _doPagination($_messages, $_pagination)
    {
        $isRecordSet = false;
        $totalCount = 0;
        if ($_messages instanceof Tinebase_Record_RecordSet)
        {
            $isRecordSet = true;
            $totalCount = $_messages->getSearchTotalCount();
            $_messages = $_messages->toArray();
        }
        else
        {
            $_messages = (Array) $_messages;
        }
        if (!(empty($_pagination->limit))){
            $limit = $_pagination->limit;
        }else{
            $limit = count($_messages);
            if ($limit == 0){
                $limit = 1;
            }
        }
        $_messages = array_chunk($_messages, $limit, true);
        $chunkIndex = (empty($_pagination->start)) ? 
                0 : 
                (($_pagination->start/$limit > count($_messages) || $_pagination->start < 0) ? 
                        (count($_messages) - 1) : 
                        $_pagination->start/$limit);

        if (empty($_messages) || count($_messages) <= $chunkIndex)
        {
            return array();
        }
        else
        {
            if ($isRecordSet)
            {
                return $this->_rawDataToRecordSet($_messages[$chunkIndex], $totalCount);
            }
            return $_messages[$chunkIndex];
        }
    }

    /*************************** abstract functions ****************************/
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string|boolean                 $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return Tinebase_Record_RecordSet|array
     *
     * @todo implement optimizations on flags and security sorting
     * @todo implement messageuid,account_id search
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')
    {
        $return = null;
        $resultIds = array();
        $messages = array();
        $filterObjects = $_filter->getFilterObjects();
        $imapFilters = $this->_parseFilterGroup($_filter, $_pagination);
        $pagination = !$_pagination ? new Tinebase_Model_Pagination(NULL, TRUE) : $_pagination;
        $searchTotalCount = 0;
        
         if($imapFilters['filters'] == 'Id'){
            $ids = $filterObjects[0]->getValue();
            $ids = $this->_doPagination($ids, $pagination);
            if($_cols === TRUE)
                return empty($ids) ? array() : $ids;
            else
                return empty($ids) ? $this->_rawDataToRecordSet(array()) : $this->getMultiple($ids);
        }else{
            
            $settings = Expressomail_Controller::getInstance()->getConfigSettings(TRUE);
            $maxresults = $settings[Expressomail_Config::IMAPSEARCHMAXRESULTS];

            if (empty($imapFilters['paths']))
            {
                $imapFilters['paths'] = $this->_getAllFolders();
            }
            
            // get Summarys and merge results
            foreach (array_keys($imapFilters['paths']) as $folderId)
            {
                if (isset($imapFilters['paths'][$folderId]['isSelectable']) && $imapFilters['paths'][$folderId]['isSelectable'] === false) continue;
                $folder = Expressomail_Backend_Folder::decodeFolderUid($folderId);
                $accountId = $folder['accountId'];
                $globalname = $folder['globalName'];

                $imap = Expressomail_Backend_ImapFactory::factory($accountId);
                $imap->selectFolder($globalname);
                $sort = $this->_getImapSortParams($_pagination);
                $idsInFolder = $imap->sort((array)$sort, (array)$imapFilters['filters']);

                if ($_cols === true) {
                    foreach ($idsInFolder as $idInFolder) {
                        $resultIds[] = self::createMessageId($folder['accountId'], $folderId, $idInFolder);
                    }
                    unset($idsInFolder);
                    unset($idInFolder);
                } else {
                    $searchTotalCount += count($idsInFolder);
                
                    if (count($imapFilters['paths']) !== 1 && $searchTotalCount > $maxresults) // when searching more than one folder, break on 1000 records
                    {
                        throw new Expressomail_Exception_IMAPCacheTooMuchResults();
                    }

                    if(count($imapFilters['paths']) === 1 && count($_cols) == 2 && $_cols[0] == '_id_' && $_cols[1] == 'messageuid')
                    {
                        $return = array();
                        $aux = Expressomail_Backend_Folder::decodeFolderUid($folderId);
                        foreach ($idsInFolder as $value)
                        {
                            $messageId = self::createMessageId($aux['accountId'], $folderId, $value);
                            $return[$messageId] = $value;
                        }
                        return $return;
                    }

                    $idsInFolder = (count($imapFilters['paths']) === 1) ? $this->_doPagination($idsInFolder, $_pagination) : $idsInFolder; // do pagination early
                    $messagesInFolder = $this->_getSummary($imap, $idsInFolder, $folderId);
                    //$messagesInFolder = $imap->getSummary($idsInFolder, null, null, $folderId);

                    if (count($imapFilters['paths']) === 1)
                    {
                        $tmp = array();
                        // We cannot trust the order we get from getSummary(), so we'll have to
                        // put it the right order, defined by $idsInFolder
                        // TODO: Put it into Felamilail_Backend_Imap->getSummary()????
                        foreach ($idsInFolder as $id){
                            $tmp[$id] = $messagesInFolder[$id];
                        }
                        $messagesInFolder = $tmp;
                        unset($tmp);
                    }
                    unset($idsInFolder);

                    $messages = array_merge($messages, $messagesInFolder);
                    unset($messagesInFolder);
                }
            }

            if ($_cols === true)
                return $resultIds;

            if ((count($imapFilters['paths']) === 1 && !in_array($pagination->sort, $this->_imapSortParams)) ||
                count($imapFilters['paths']) > 1) // do not sort
            {
                $callback = new Expressomail_Backend_MessageComparator($pagination);
                uasort($messages, array($callback, 'compare'));
            }
        }

        if (empty($messages))
        {
            return $this->_rawDataToRecordSet(array());
        }

        // Apply Pagination and get the resulting summary
        $messages = count($imapFilters['paths']) === 1 ? $messages : $this->_doPagination($messages, $_pagination);

        $return =  empty($messages) ? $this->_rawDataToRecordSet(array()) :
            $this->_rawDataToRecordSet($this->_createModelMessageArray($messages), $searchTotalCount);

        return $return;
    }

   /**
     * It do nothing. Only maintain public interface compatibility
     *
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface Record
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        return $_record;
    }


//        /**
//     * Updates multiple entries
//     *
//     * @param array $_ids to update
//     * @param array $_data
//     * @return integer number of affected rows
//     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
//     */
//    public function updateMultiple($_ids, $_data)
//    {
//        return Null;
//    }


    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $result = $this->_getIds($this->_parseFilterGroup($_filter));

        $ids = array();
        foreach ($result as $tmp)
        {
            $ids = array_merge($ids, $tmp);
        }

        $return = count($ids);
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . 'Message searchCount = $retorno ' . print_r($retorno,true));
        return $return;
    }

    /**
     * Gets one entry (by id)
     *
     * @param integer|Tinebase_Record_Interface $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function get($_id, $_getDeleted = FALSE)
    {
/*
Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message get = $_id ' . print_r($_id,true));
Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message get = $_getDeleted' . print_r($_getDeleted,true));
*/
        $retorno = null;
        $decodedIds = self::decodeMessageId($_id);
        if ($decodedIds['accountId']){
            $uid = $decodedIds['messageUid'];
            $folder = Expressomail_Controller_Folder::getInstance()->get($decodedIds['folderId']);
            $globalname = Expressomail_Backend_Folder::decodeFolderUid($decodedIds['folderId']);
            $accountId = $decodedIds['accountId'];

            $imap = Expressomail_Backend_ImapFactory::factory($accountId);
            $imap->selectFolder($globalname['globalName']);

            // we're getting just one message
            $messages = $imap->getSummary($uid, $uid, TRUE, $folder->getId()); // $folder->getId() = ugly hack, have to try to find another solution

            if (count($messages) === 0)
            {
                throw new Tinebase_Exception_NotFound("Message number $uid not found!");
            }

            $message = array_shift($messages);
            $retorno = $this->_createModelMessage($message, $folder);
        }
        return $retorno;
    }

     /**
      * Deletes entries
      *
      * @param string|integer|Tinebase_Record_Interface|array $_id
      * @return void
      * @return int The number of affected rows.
      */
    public function delete($_id)
    {
        $_id = ($_id instanceof Expressomail_Model_Message) ? array($_id->getId()) : $_id;
        if(is_array($_id)){
            foreach($_id as $id){
                $decodedIds = self::decodeMessageId($id);
                $globalname = Expressomail_Backend_Folder::decodeFolderUid($decodedIds['folderId']);
                $accountId = $decodedIds['accountId'];
                $imap = Expressomail_Backend_ImapFactory::factory($accountId);
                $imap->expunge($globalname['globalName']);
                $return = count($_id);
            }
        }

        return $return;
    }





    /**
     * Get multiple entries
     *
     * @param string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_Record_RecordSet
     *
     * @todo get custom fields here as well
     */
    public function getMultiple($_id, $_containerIds = NULL)
    {
       if($_id instanceof Tinebase_Record_RecordSet)
           return $_id;
       $messages = array();
       $ids = array();
        if(is_array($_id)){
            $ids = $_id;
        }else{
            $ids[] = $_id;
        }
        foreach($ids as $id){
            $message = $this->get($id);
            if (!(empty($message))){
                $messages[] = $message;
            }
        }
        if (count($messages)>0){
            $retorno = new Expressomail_Record_SearchTotalCountRecordSet('Expressomail_Model_Message', $messages, true);
            $retorno->setSearchTotalCount(count($messages));
        }else{
            $retorno = new Expressomail_Record_SearchTotalCountRecordSet('Expressomail_Model_Message');
            $retorno->setSearchTotalCount(0);
        }
        return $retorno;
    }

//    /**
//     * Creates new entry
//     *
//     * @param   Tinebase_Record_Interface $_record
//     * @return  Tinebase_Record_Interface
//     * @throws  Tinebase_Exception_InvalidArgument
//     * @throws  Tinebase_Exception_UnexpectedValue
//     *
//     * @todo    remove autoincremental ids later
//     */
//    public function create(Tinebase_Record_Interface $_record)
//    {
///*
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message create = $_record ' . print_r($_record,true));
//*/
////        $aux = new Expressomail_Backend_Message();
////        $retorno = $aux->create($_record);
//
////Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . 'Message create = $retorno ' . print_r($retorno,true));
//        return NULL;
//    }


    /*************************** interface functions ****************************/
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @return array
     */
    public function searchMessageUids(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)
    {
        return $this->search($_filter, $_pagination, array(Tinebase_Backend_Sql_Abstract::IDCOL, 'messageuid'));
    }

//    /**
//     * get all flags for a given folder id
//     *
//     * @param string|Expressomail_Model_Folder $_folderId
//     * @param integer $_start
//     * @param integer $_limit
//     * @return Tinebase_Record_RecordSet
//     */
//    public function getFlagsForFolder($_folderId, $_start = NULL, $_limit = NULL)
//    {
//        $filter = $this->_getMessageFilterWithFolderId($_folderId);
//        $pagination = ($_start !== NULL || $_limit !== NULL) ? new Tinebase_Model_Pagination(array(
//            'start' => $_start,
//            'limit' => $_limit,
//        ), TRUE) : NULL;
//
//        return $this->search($filter, $pagination, array('messageuid' => 'messageuid', 'id' => Tinebase_Backend_Sql_Abstract::IDCOL, 'flags' => 'felamimail_cache_message_flag.flag'));
//    }

//    /**
//     * add flag to message
//     *
//     * @param Expressomail_Model_Message $_message
//     * @param string $_flag
//     */
//    public function addFlag($_message, $_flag)
//    {
///*
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message addFlag = $_message ' . print_r($_message,true));
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message addFlag = $_flag ' . print_r($_flag,true));
//*/
////        $aux = new Expressomail_Backend_Message();
////        $aux->addFlag($_message, $_flag);
//        return NULL;
//    }

//    /**
//     * set flags of message
//     *
//     * @param  mixed         $_messages array of ids, recordset, single message record
//     * @param  string|array  $_flags
//     */
//    public function setFlags($_messages, $_flags, $_folderId = NULL)
//    {
///*
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message setFlags = $_message ' . print_r($_message,true));
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message setFlags = $_flags ' . print_r($_flags,true));
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message setFlags = $_folderId ' . print_r($_folderId,true));
//*/
////        $aux = new Expressomail_Backend_Message();
////        $aux->setFlags($_messages, $_flags, $_folderId);
//    }

//    /**
//     * remove flag from messages
//     *
//     * @param  mixed  $_messages
//     * @param  mixed  $_flag
//     */
//    public function clearFlag($_messages, $_flag)
//    {
///*
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message clearFlag = $_message ' . print_r($_message,true));
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message clearFlag = $_flag ' . print_r($_flag,true));
//*/
////        $aux = new Expressomail_Backend_Message();
////        $aux->clearFlag($_messages, $_flag);
//    }

//    /**
//     * Does nothing in this backend. It's necessary for the interface though.
//     *
//     * @param  mixed  $_folderId
//     */
//    public function deleteByFolderId($_folderId)
//    {
//        /**
//         *TODO: remove the rest of the function
//         */
////        $aux = new Expressomail_Backend_Message();
////        $aux->deleteByFolderId($_folderId);
//    }

//    /**
//     * get count of cached messages by folder (id)
//     *
//     * @param  mixed  $_folderId
//     * @return integer
//     */
//    public function searchCountByFolderId($_folderId)
//    {
///*
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message searchCountByFolderId = $_folderId ' . print_r($_folderId,true));
//*/
////        $aux = new Expressomail_Backend_Message();
////        $retorno = $aux->searchCountByFolderId($_folderId);
//
////Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . 'Message searchCountByFolderId = $retorno ' . print_r($retorno,true));
//        return NULL;
//    }

//    /**
//     * get count of seen cached messages by folder (id)
//     *
//     * @param  mixed  $_folderId
//     * @return integer
//     *
//     */
//    public function seenCountByFolderId($_folderId)
//    {
///*
//Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Message seenCountByFolderId = $_folderId ' . print_r($_folderId,true));
//*/
////        $aux = new Expressomail_Backend_Message();
////        $retorno = $aux->seenCountByFolderId($_folderId);
//
////Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . 'Message seenCountByFolderId = $retorno ' . print_r($retorno,true));
//        return NULL;
//    }

    /**
     * delete messages with given messageuids by folder (id)
     *
     * @param  array  $_msguids
     * @param  mixed  $_folderId
     * @return integer number of deleted rows or false if no message are given
     */
    public function deleteMessageuidsByFolderId($_msguids, $_folderId)
    {
        $return = FALSE;
        if (!(empty($_msguids) || !is_array($_msguids)))
        {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Logicaly delete the messages '
                                                                                            . print_r($_msguids, true));
            $return = count($_msguids);
        }
        //return $return;
        /**
         * TODO: remove the code below and uncomment the code above
         */
        $aux = new Expressomail_Backend_Message();
        $retorno = $aux->deleteMessageuidsByFolderId($_msguids, $_folderId);
        return $retorno;
    }

/********************************************** protected functions ***************************************************/

//    /**
//     * converts raw data from adapter into a single record
//     *
//     * @param  array $_rawData
//     * @return Tinebase_Record_Abstract
//     */
//    protected function _rawDataToRecord(array $_rawData)
//    {
//        if (isset($_rawData['structure'])) {
//            $_rawData['structure'] = Zend_Json::decode($_rawData['structure']);
//        }
//
//        $result = parent::_rawDataToRecord($_rawData);
//
//        return $result;
//    }

//    /**
//     * converts record into raw data for adapter
//     *
//     * @param  Tinebase_Record_Abstract $_record
//     * @return array
//     */
//    protected function _recordToRawData($_record)
//    {
//        $result = parent::_recordToRawData($_record);
//
//        if(isset($result['structure'])) {
//            $result['structure'] = Zend_Json::encode($result['structure']);
//        }
//
//        return $result;
//    }

//    /**
//     * get folder id message filter
//     *
//     * @param mixed $_folderId
//     * @return Expressomail_Model_MessageFilter
//     */
//    protected function _getMessageFilterWithFolderId($_folderId)
//    {
//        $folderId = ($_folderId instanceof Expressomail_Model_Folder) ? $_folderId->getId() : $_folderId;
//        $filter = new Expressomail_Model_MessageFilter(array(
//            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $folderId)
//        ));
//
//        return $filter;
//    }

    /**
     * converts raw data from adapter into a set of records
     * got from Tinebase_Backend_Sql_Abstract
     *
     * @param  array $_rawDatas of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array $_rawDatas, $_totalCount = 0)
    {
        $result = new Expressomail_Record_SearchTotalCountRecordSet($this->_modelName, $_rawDatas, true);
        $result->setSearchTotalCount($_totalCount);

        return $result;
    }
}
