<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        set timestamp field (add default to model?)
 */

/**
 * sql backend class for Expressomail folders
 *
 * @package     Expressomail
 */
class Expressomail_Backend_Folder //extends Tinebase_Backend_Sql_Abstract
{
     private $_accounts = null;
     const IMAPDELIMITER = '/';
     public static $SYSTEM_FOLDERS_DEFAULT = array('Sent', 'Trash', 'Drafts', 'Templates');

     /**
     * the constructor
     * Caches a Expressomail_Account object for latter use.
     * @return void
     */

    public function __construct()
    {
        $accounts = array();
        $record = Expressomail_Controller_Account::getInstance()->search();
        $record = $record->toArray();
        foreach ($record as $account){
              $accounts[$account['id']] = new Expressomail_Model_Account($account,true);
        }
        $this->_accounts = $accounts;
    }

    /*************************** abstract functions ****************************/
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  boolean                              $_useCache
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  array|string|boolean                 $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, $_useCache = TRUE, Tinebase_Model_Pagination $_pagination = NULL, $_cols = '*')
    {
        $filters = $_filter->getFilterObjects();

//        TODO: implement this folder filter
//        $folderFilter = new Expressomail_Model_FolderFilter(array(
//                        array('field' => 'account_id',  'operator' => 'in',     'value' => $accounts->getArrayOfIds()),
//                        array('field' => 'localname',   'operator' => 'equals', 'value' => 'INBOX')
//        ));


        foreach($filters as $filter)
        {
            switch($filter->getField())
            {
                case 'account_id':
                    $accountId = $filter->getValue();
                    break;
                case 'parent':
                    $globalName = $filter->getValue();
                    $parent = true;
                    break;
                case 'id':
                    $ids = array_keys($this->_accounts);
                    $accountId = $ids[0];
                    $globalName = $filter->getValue();
                    $parent = true;
                    break;
                case 'globalname':
                    $globalName = $filter->getValue();
                    if($filter->getOperator() == 'startswith'){
                        $parent = true;
                        $globalName = substr($globalName, 0, -1);
                    }
                    break;
            }
        }

        $resultArray = array();
        $accountId = (array)$accountId;

        foreach ($accountId as $id)
        {
            $account = $this->_accounts[$id];

            if($parent === true){
                $folders = $this->_getFoldersFromIMAP($account, $globalName);
                foreach($folders as $folder)
                {
                    $resultArray[] = $this->get(self::encodeFolderUid($folder['globalName'],$id), $_useCache);
                }
            }
            else{
                $resultArray[] = $this->get(self::encodeFolderUid(Expressomail_Model_Folder::encodeFolderName($globalName),$id), $_useCache);
            }
        }

        $result = new Tinebase_Record_RecordSet('Expressomail_Model_Folder', $resultArray, true);

        return $result;
    }

    public function getAllFolderIdsFromAccount($_accountId)
    {
        $return = array();
        $account = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId : Expressomail_Controller_Account::getInstance()->get($_accountId);
        $imap = Expressomail_Backend_ImapFactory::factory($account);
        $folders = $imap->getFolders('', '*', $account);
        foreach ($folders as $folder)
        {
            //$folderId = self::encodeFolderUid($folder, $account->id);
            if ($folder['isSelectable'])
            {
                $return[self::encodeFolderUid($folder['globalName'], $account->id)] = array($account->id, Expressomail_Model_Folder::decodeFolderName($folder['globalName']));
            }
        }
        return $return;
    }

    /**
     * Get all folders (ActiveSync Tunning)
     * @param string $accountId
     * @return array
     */
    public function getAllFoldersAS(Expressomail_Model_Account $account)
    {
        $resultArray = array();
        $accountId = $account->getId();

        foreach($this->_getFoldersFromIMAP($account) as $folder)
        {

            $folderId = self::encodeFolderUid($folder['globalName'],$accountId);
            if($folder['globalName'] == 'INBOX' || $folder['globalName'] == 'user')
            {
                $parentId = '0';
            }
            else
            {
                $parentId = self::encodeFolderUid(substr($folder['globalName'],0 , strrpos($folder['globalName'],self::IMAPDELIMITER)), $accountId);
            }

            $resultArray[$folderId]['folderId'] = $folderId;
            $resultArray[$folderId]['parentId'] = $parentId;
            $resultArray[$folderId]['displayName'] = $folder['localName'];
            $resultArray[$folderId]['isSelectable'] = $folder['isSelectable'];
            $resultArray[$folderId]['type'] = '';
        }

        foreach ($this->_getFoldersFromIMAP($account, '*') as $folder)
        {
            $folderId = self::encodeFolderUid($folder['globalName'],$accountId);
            if($folder['globalName'] == 'INBOX' || $folder['globalName'] == 'user')
            {
                $parentId = '0';
            }
            else
            {
                $parentId = self::encodeFolderUid(substr($folder['globalName'],0 , strrpos($folder['globalName'],self::IMAPDELIMITER)), $accountId);
            }

            $resultArray[$folderId]['folderId'] = $folderId;
            $resultArray[$folderId]['parentId'] = $parentId;
            $resultArray[$folderId]['displayName'] = $folder['localName'];
            $resultArray[$folderId]['isSelectable'] = $folder['isSelectable'];
            $resultArray[$folderId]['type'] = '';

        }

        return $resultArray;
    }

    /**
     * get folders from imap
     *
     * @param Expressomail_Model_Account $_account
     * @param mixed $_folderName
     * @return array
     */
    protected function _getFoldersFromIMAP(Expressomail_Model_Account $_account, $_folderName = null)
    {
        if (empty($_folderName))
        {
            $folders = $this->_getRootFolders($_account);
        } else {
            if (!is_array($_folderName))
            {
                $folders = $this->_getSubfolders($_account, $_folderName);
            }
            else
            {
                $folders = array();
                foreach ($_folderName as $folder)
                {
                    $decodedFolder = self::decodeFolderUid($folder);
                    $folders = array_merge($folders, $this->_getFolder($_account, Expressomail_Model_Folder::decodeFolderName($decodedFolder['globalName'])));
                }
            }
        }

        return $folders;
    }

    /**
     * get root folders and check account capabilities and system folders
     *
     * @param Expressomail_Model_Account $_account
     * @return array of folders
     */
    protected function _getRootFolders(Expressomail_Model_Account $_account)
    {
        $imap = Expressomail_Backend_ImapFactory::factory($_account);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Get subfolders of root for account ' . $_account->getId());
        $result = $imap->getFolders('', '%', $_account);

        return $result;
    }

    /**
     * get root folders and check account capabilities and system folders
     *
     * @param Expressomail_Model_Account $_account
     * @return array of folders
     */
    protected function _getFolder(Expressomail_Model_Account $_account, $_folderName)
    {
        $imap = Expressomail_Backend_ImapFactory::factory($_account);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Get folder ' . $_folderName);
        $result = $imap->getFolders(Expressomail_Model_Folder::encodeFolderName($_folderName));

        return $result;
    }

    /**
     * get subfolders
     *
     * @param $_account
     * @param $_folderName
     * @return array of folders
     */
    protected function _getSubfolders(Expressomail_Model_Account $_account, $_folderName)
    {
        $result = array();

        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' trying to get subfolders of ' . $_folderName . self::IMAPDELIMITER);

            $imap = Expressomail_Backend_ImapFactory::factory($_account);
            $result = $imap->getFolders(
                                   Expressomail_Model_Folder::encodeFolderName($_folderName) . self::IMAPDELIMITER , '%', $_account);

            // remove folder if self
            if (in_array($_folderName, array_keys($result))) {
                unset($result[$_folderName]);
            }
            
            if($_folderName == 'INBOX') {
                $actualFolders = array();
                foreach($result as $actualFolder) {
                    $actualFolders[] = $actualFolder['localName'];
                }
                // create the missing inbox sub folders
                foreach ($this->getSystemFolders() as $folder) {
                    if( ! in_array($folder, $actualFolders)) {
                        try {
                            $imap->createFolder($folder, 'INBOX');
                            if(Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                                __METHOD__ . '::' . __LINE__ . ' Create new default system folder: ' . 'INBOX' . 
                                Expressomail_Backend_Folder::IMAPDELIMITER . $folder);
                            // include new folders in the result
                            $new = $imap->getFolders('', Expressomail_Model_Folder::encodeFolderName('INBOX' . self::IMAPDELIMITER . $folder), $_account);
                            $result = array_merge($result, $new);
                        } catch (Exception $e) {
                            if(Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->info(
                                __METHOD__ . '::' . __LINE__ . 'Could not create default system folder ' . 'INBOX' . 
                                Expressomail_Backend_Folder::IMAPDELIMITER . $folder . ' (' . $e->getMessage () . ')');
                        }
                    }
                }
            }
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' No subfolders of ' . $_folderName . ' found.');
        }

        return $result;
    }




    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $folder = $_record->toArray();
        $return = $this->get($this->encodeFolderUid($folder['globalname'], $folder['account_id']), FALSE);
        Return $return;
    }

    /**
     * Gets one entry (by id)
     *
     * @param string $_id
     * @param boolean $_useCache true to get folder from cache
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     * 
     * @todo test to do a getQuota() shoudn't be hardcoded
     * 
     */
    public function get($_id, $_useCache = TRUE)
    {
        $cache = Tinebase_Core::getCache();
        $cacheKey = 'Expressomail_Model_Folder_'.$_id;
        $folderFromCache = $_useCache ? $cache->load($cacheKey) : FALSE;
        if ($folderFromCache) {
            return $folderFromCache;
        }

        $folderDecoded = self::decodeFolderUid($_id);
        if (isset($folderDecoded['accountId'])){
            $imap = Expressomail_Backend_ImapFactory::factory($folderDecoded['accountId'],TRUE);
            $folder = $imap->getFolders('',$folderDecoded['globalName'],
            $this->_accounts[$folderDecoded['accountId']]);
            //$status = $imap->examineFolder($folderDecoded['globalName']);
            $status = $imap->getFolderStatus($folderDecoded['globalName']);
            if($status === FALSE){
                // we can not access folder, create Model as unselectable
                $globalname = $folderDecoded['globalName'];
                $auxlocalname = explode(self::IMAPDELIMITER, $globalname);
                $localname = array_pop($auxlocalname);
                $translate = Tinebase_Translation::getTranslation("Expressomail");

                $newFolder = new Expressomail_Model_Folder(array(
                    'id' => $_id,
                    'account_id' => Tinebase_Core::getPreference('Expressomail')->{Expressomail_Preference::DEFAULTACCOUNT},
                    'localname' => ($localname == 'user') ? $translate->_("Shared Folders") : $localname,
                    'globalname' => $folderDecoded['globalName'],
                    'parent' => $globalname === 'user' ? '' : substr($globalname,0 , strrpos($globalname,self::IMAPDELIMITER)),
                    'delimiter' => self::IMAPDELIMITER,
                    'is_selectable' => 0,
                    'has_children' => 1,
                    'system_folder' => 1,
                    'imap_status' => Expressomail_Model_Folder::IMAP_STATUS_OK,
                    'imap_timestamp' => Tinebase_DateTime::now(),
                    'cache_status' => 'complete',
                    'cache_timestamp' => Tinebase_DateTime::now(),
                    'cache_job_lowestuid' => 0,
                    'cache_job_startuid' => 0,
                    'cache_job_actions_est' => 0,
                    'cache_job_actions_done' => 0
                ),true); 

                $cache->save($newFolder, $cacheKey);
                return $newFolder;
            }

            $globalName = $folderDecoded['globalName'];
            if($globalName == 'INBOX' || $globalName == 'user')
            {
                $folder[$folderDecoded['globalName']]['parent'] = '';
            }
            else
            {
                $folder[$folderDecoded['globalName']]['parent'] = substr($globalName,0 , strrpos($globalName,self::IMAPDELIMITER));
            }
            /*
                * @todo must see if it is not better do this on the model directly
                */
            $systemFolders = FALSE;
            if (strtolower($globalName) === 'inbox' || strtolower($folder[$folderDecoded['globalName']]['parent']) === 'user')
            {
                $systemFolders = TRUE;
            }
            else if (strtolower($folder[$folderDecoded['globalName']]['parent']) === 'inbox' && strtolower($folder[$folderDecoded['globalName']]['localName']) !== 'inbox')
            {
                $systemFolders = in_array(strtolower($folder[$folderDecoded['globalName']]['localName']),
                                                        Expressomail_Controller_Folder::getInstance()->getSystemFolders($folderDecoded['accountId']));
            }
            else if(preg_match ( '/^user\/[^\/]+$/i', $folder[$folderDecoded['globalName']]['parent'])){
                $systemFolders = in_array(strtolower($folder[$folderDecoded['globalName']]['localName']),
                                                       Expressomail_Controller_Folder::getInstance()->getSystemFolders($folderDecoded['accountId']));
            }
            $localName = Expressomail_Model_Folder::decodeFolderName($folder[$folderDecoded['globalName']]['localName']);

            if(preg_match("/^user\/[0-9]{11}$/",Expressomail_Model_Folder::decodeFolderName($folder[$folderDecoded['globalName']]['globalName'])))
            {
                    try
                    {
                        $aux = Tinebase_User::getInstance()->getFullUserByLoginName($localName)->toArray();
                        $localName = $aux["accountFullName"];
                    }
                    catch(Exception $exc)
                    {

                    }
            }

            $expressomailSession = Expressomail_Session::getSessionNamespace();
            $userNameSpace = $imap->getUserNameSpace() . self::IMAPDELIMITER;
            $arrDecodedFolder = explode(self::IMAPDELIMITER, $folderDecoded['globalName']);
            if (
                    $folderDecoded['globalName'] === 'INBOX' ||
                    $folderDecoded['globalName'] === 'INBOX' . self::IMAPDELIMITER . 'Arquivo Remoto' ||
                    (
                        //if folder is something like 'user/<user_id>' AND the "third" arrfolder parameter is NOT set
                        //(which means is 'user/<user_id>' only) OR the "third" arrfolder parameter is set and is equal
                        //to 'Arquivo Remoto' "
                        (substr($folderDecoded['globalName'] , 0 , strlen($userNameSpace)) === $userNameSpace) &&
                        (!(isset($arrDecodedFolder[2])) || ($arrDecodedFolder[2] === 'Arquivo Remoto'))
                    )
                )
            {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Getting quota from IMAP for ' . $folderDecoded['globalName']);
                }
                $quota = $imap->getQuota($folderDecoded['globalName']);
                $expressomailSession->quota[$folderDecoded['globalName']] = $quota;
            }else {
                if($arrDecodedFolder[0] === 'INBOX' && isset($arrDecodedFolder[1]) && $arrDecodedFolder[1] === 'Arquivo Remoto'){
                    $globalNameFolder = $arrDecodedFolder[0] . self::IMAPDELIMITER . $arrDecodedFolder[1];
                }else{
                    $globalNameFolder = $arrDecodedFolder[0];
                }

                if ($arrDecodedFolder[0] !== 'INBOX'){
                    $globalNameFolder .= (isset($arrDecodedFolder[1]))?self::IMAPDELIMITER . $arrDecodedFolder[1]:'';
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Getting quota from Session for ' . $folderDecoded['globalName']);
                }
                $quota = isset($expressomailSession->quota[$globalNameFolder]) ? $expressomailSession->quota[$globalNameFolder] : 0;
            }

            $return = new Expressomail_Model_Folder(array(
                    'id' => $_id,
                    'account_id' => $folderDecoded['accountId'],
                    'localname' => $localName,
                    'globalname' => Expressomail_Model_Folder::decodeFolderName($folder[$folderDecoded['globalName']]['globalName']),
                    'parent' => Expressomail_Model_Folder::decodeFolderName($folder[$folderDecoded['globalName']]['parent']),
                    'delimiter' => $folder[$folderDecoded['globalName']]['delimiter'],
                    'is_selectable' => $folder[$folderDecoded['globalName']]['isSelectable'],
                    'has_children' => $folder[$folderDecoded['globalName']]['hasChildren'],
                    'system_folder' => $systemFolders,
                    'imap_status' => Expressomail_Model_Folder::IMAP_STATUS_OK,
                    'imap_uidvalidity' => $status['uidvalidity'],
                    'imap_totalcount' => (array_key_exists('messages', $status))?$status['messages']:'',
                    'imap_timestamp' => Tinebase_DateTime::now(),
                    'cache_status' => 'complete',
                    'cache_totalcount' =>  (array_key_exists('messages', $status))?$status['messages']:'',
                    'cache_recentcount' => (array_key_exists('recent', $status))?$status['recent']:'',
                    'cache_unreadcount' => (array_key_exists('unseen', $status))?$status['unseen']:'',
                    'cache_timestamp' => Tinebase_DateTime::now(),
                    'cache_job_lowestuid' => 0,
                    'cache_job_startuid' => 0,
                    'cache_job_actions_est' => 0,
                    'cache_job_actions_done' => 0,
                    'quota_usage'   => !empty($quota) ? $quota['STORAGE']['usage'] : 0,
                    'quota_limit'   => !empty($quota) ? $quota['STORAGE']['limit'] : 0,
                ),true);

             $cache->save($return, $cacheKey);
             return $return;
        }
    }
    
    
    /**
     *
     * @param Expressomail_Model_Account $_account
     * @param type $_folderName
     * @return type 
     */
    function getFolders(Expressomail_Model_Account $_account, $_folderName){
        if (empty($_folderName)) {
            $folders = $this->_getRootFolders($_account);
        } else {
            $folders = $this->_getSubfolders($_account, $_folderName);
        }
        
        return $folders;
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
        $return = array();
        if(is_array($_id)){
            foreach($_id as $id){
                $folder = $this->get($id);
                $return[] = $folder;
            }
        }else{
            $return[] = $this->get($_id);
        }

        return   new Tinebase_Record_RecordSet('Expressomail_Model_Folder', $return, true);

    }

    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     *
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $folder = $_record->toArray();
        $return = $this->get($this->encodeFolderUid(Expressomail_Model_Folder::encodeFolderName($folder['globalname']), $folder['account_id']), FALSE);
        Return $return;
    }

/*************************** interface functions ****************************/
    /**
     * get folder cache counter like total and unseen
     *
     * @param  string  $_folderId  the folderid
     * @return array
     */
    public function getFolderCounter($_folderId)
    {
        if($_folderId instanceof Expressomail_Model_Folder){
            $exists = $_folderId->cache_totalcount;
            $unseen = $_folderId->cache_unreadcount;
        }else{
            $folder = self::decodeFolderUid($_folderId);
            $imap = Expressomail_Backend_ImapFactory::factory($folder['accountId']);
            $counter = $imap->examineFolder($folder['globalName']);
            $exists = $counter['exists'];
            $unseen = $counter['unseen'];
        }
         return array(
            'cache_totalcount'  => $exists,
            'cache_unreadcount' => $unseen
        );
    }
    
    /**
    * get folder ids of all inboxes for accounts of current user
    *
    * @return array
    */
    public function getAllInboxes()
    {
        $return = array();
        $accounts = Expressomail_Controller_Account::getInstance()->search();
//        $folderFilter = new Expressomail_Model_FolderFilter(array(
//            array('field' => 'account_id', 'operator' => 'in', 'value' => $accounts->getArrayOfIds()),
//            array('field' => 'globalname', 'operator' => 'equals', 'value' => 'INBOX')
//        ));
//        $folderIds = Expressomail_Controller_Folder::getInstance()->search($folderFilter, NULL, TRUE);

        foreach ($accounts as $account){
             // Should be true to every imap server
            $return[] = '/'.$account->id .
                        '/' . $this->encodeFolderUid('INBOX', $account->id);
        }
        
//        //get array of /account_id/id
//        for ($it = $folderIds->getIterator(), $it->rewind(); $it->valid(); $it->next())
//        {
//            $folder = $it->current();
//            $return[] = self::IMAPDELIMITER.$folder->account_id.self::IMAPDELIMITER.$folder->id;
//        }

        return $return;
    }

    /**
     * increment/decrement folder counter on sql backend
     *
     * @param  mixed  $_folderId
     * @param  array  $_counters
     * @return Expressomail_Model_Folder
     * @throws Tinebase_Exception_InvalidArgument
     */
     public function updateFolderCounter($_folderId, array $_counters)
    {
        /*
        Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Folder create = $_folderId ' . print_r($_folderId,true));
        Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . ' Folder create = $_counters ' . print_r($_counters,true));
        */
       $folder = ($_folderId instanceof Expressomail_Model_Folder) ? $_folderId : $this->get($_folderId, FALSE);

        //Tinebase_Core::getLogger()->alert(__METHOD__ . '#####::#####' . __LINE__ . 'Folder create = $retorno ' . print_r($retorno,true));
        return $folder;
    }

    /**
     * Encode the folder name to be passed on the calls
     * @param string $_folder
     * @param string $_accountId
     * @return string
     */
    static public function encodeFolderUid($_folder,$_accountId)
    {
        $folder = base64_encode($_accountId.";".$_folder);
        $count = substr_count($folder, '=');
      return substr($folder,0, (strlen($folder) - $count)) . $count;
    }

    /**
     * Decode the folder previously encoded by encoderFolderUid
     * @param string $_folder
     * @return array
     */
    static public function decodeFolderUid($_folder)
    {
        $return = array(
            'accountId'     => null,
            'globalName'    => null,
        );
        $strPadNumeric = substr($_folder, -1);
        if (is_numeric($strPadNumeric)){
            $decoded = base64_decode(str_pad(substr($_folder, 0, -1), $strPadNumeric, '='));
            if ($decoded){
                $arrParts = explode(';', $decoded);
                if (count($arrParts) > 1 ){
                    list($return['accountId'], $return['globalName']) = $arrParts;
                }
            }
        }
        return $return;
    }
    
    /**
     * get system folders
     *
     * @return array
     */
    public function getSystemFolders()
    {
        return Expressomail_Backend_Folder::$SYSTEM_FOLDERS_DEFAULT;
    }
}
