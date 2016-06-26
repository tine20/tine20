<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Protocol
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Expressomail IMAP backend
 *
 * @package     Expressomail
 * @subpackage  Protocol
 */
class Expressomail_Protocol_Imap extends Zend_Mail_Protocol_Imap
{
    /**
     * timeout in seconds for initiating session (parent: 30)
     */
    const TIMEOUT_CONNECTION = 20;

//    /**
//     * fetch one or more items of one or more messages
//     *
//     * @param  string|array $items items to fetch from message(s) as string (if only one item)
//     *                             or array of strings
//     * @param  int          $from  message for items or start message if $to !== null
//     * @param  int|null     $to    if null only one message ($from) is fetched, else it's the
//     *                             last message, INF means last message avaible
//     * @return string|array if only one item of one message is fetched it's returned as string
//     *                      if items of one message are fetched it's returned as (name => value)
//     *                      if one items of messages are fetched it's returned as (msgno => value)
//     *                      if items of messages are fetchted it's returned as (msgno => (name => value))
//     * @throws Zend_Mail_Protocol_Exception
//     */
//    public function fetch($items, $from, $to = null, $uid = false)
//    {
//        if (is_array($from)) {
//            $set = implode(',', $from);
//        } else if ($to === null) {
//            $set = (int)$from;
//        } else if ($to === INF) {
//            $set = (int)$from . ':*';
//        } else {
//            $set = (int)$from . ':' . (int)$to;
//        }
//
//        $items = (array)$items;
//        $itemList = $this->escapeList($items);
//
//        $this->sendRequest($uid ? 'UID FETCH' : 'FETCH', array($set, $itemList), $tag);
//
//        // BODY.PEEK gets returned as BODY
//        foreach($items as &$item) {
//            if (substr($item, 0, 9) == 'BODY.PEEK') {
//                $item = 'BODY' . substr($item, 9);
//            }
//        }
//
//        $result = array();
//        while (!$this->readLine($tokens, $tag)) {
//            // ignore other responses
//            if ($tokens[1] != 'FETCH') {
//                continue;
//            }
//
//            $data = array();
//            while (key($tokens[2]) !== null) {
//                $data[current($tokens[2])] = next($tokens[2]);
//                next($tokens[2]);
//            }
//
//            // ignore other messages
//            // with UID FETCH we get the ID and NOT the UID as $tokens[0]
//            #if ($to === null && !is_array($from) && $tokens[0] != $from) {
//            #    continue;
//            #}
//
//            // if we want only one message we can ignore everything else and just return
//            if ($to === null && !is_array($from) && (($uid !== true && $tokens[0] == $from) || ($uid === true && $data['UID'] == $from))) {
//                // we still need to read all lines
//                while (!$this->readLine($tokens, $tag));
//                return (count($items) == 1) ? $data[$items[0]] : $data;
//            }
//
//            $messageId = $uid === true ? $data['UID'] : $tokens[0];
//            $result[$messageId] = (count($items) == 1) ? $data[$items[0]] : $data;
//        }
//
//        if ($to === null && !is_array($from)) {
//            /**
//             * @see Zend_Mail_Protocol_Exception
//             */
//            require_once 'Zend/Mail/Protocol/Exception.php';
//            throw new Zend_Mail_Protocol_Exception('the single id was not found in response');
//        }
//
//        return $result;
//    }

//    /**
//     * set flags
//     *
//     * @param  array       $flags  flags to set, add or remove - see $mode
//     * @param  int         $from   message for items or start message if $to !== null
//     * @param  int|null    $to     if null only one message ($from) is fetched, else it's the
//     *                             last message, INF means last message avaible
//     * @param  string|null $mode   '+' to add flags, '-' to remove flags, everything else sets the flags as given
//     * @param  bool        $silent if false the return values are the new flags for the wanted messages
//     * @return bool|array new flags if $silent is false, else true or false depending on success
//     * @throws Zend_Mail_Protocol_Exception
//     */
//    public function store(array $flags, $from, $to = null, $mode = null, $silent = true, $uid = false)
//    {
//        $item = 'FLAGS';
//        if ($mode == '+' || $mode == '-') {
//            $item = $mode . $item;
//        }
//        if ($silent) {
//            $item .= '.SILENT';
//        }
//
//        $flags = $this->escapeList($flags);
//        $set = (int)$from;
//        if ($to != null) {
//            $set .= ':' . (is_infinite($to) ? '*' : (int)$to);
//        }
//
//        $result = $this->requestAndResponse($uid ? 'UID STORE' : 'STORE', array($set, $item, $flags), $silent);
//
//        if ($silent) {
//            return $result ? true : false;
//        }
//
//        $tokens = $result;
//        $result = array();
//        foreach ($tokens as $token) {
//            if ($token[1] != 'FETCH' || $token[2][0] != 'FLAGS') {
//                continue;
//            }
//            $result[$token[0]] = $token[2][1];
//        }
//
//        return $result;
//    }

        /**
     * get acls for a folder
     *
     * @param  string $box which folder to get the acls
     * @param  bool $returnOwnerACL true if it will return owner's ACL
     * @return bool|array false if error, array with all users and the acls of this user for this folder.
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getFolderAcls($box = 'INBOX', $returnOwnerACL = FALSE){

        $this->sendRequest("GETACL", array($this->escapeString($box)), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            $result = $tokens;
        }

        if ($tokens[0] != 'OK') {
            return false;
        }else{
            $results = Array();
            for($i = 2; $i < count($result); $i = $i+2){
                $writeacl = false;
                $readacl = false;
                $sendacl = false;
                $administer = false;

                if(stristr($result[$i+1],'w')){
                    $writeacl = true;
                }
                if(stristr($result[$i+1],'r')){
                    $readacl = true;
                }
                if(stristr($result[$i+1],'p')){
                    $sendacl = true;
                }
                if (stristr($result[$i+1],'a')) {
                    $administer = true;
                }
                try{
                    if($this->_useUidAsLogin()){
                        $user = Tinebase_User::getInstance()->getFullUserByLoginName($result[$i])->toArray();
                    }else{
                        $user = Tinebase_User::getInstance()->getFullUserByEmailAddress($result[$i])->toArray();
                    }
                    $current = Tinebase_Core::getUser()->toArray();

                    if(!$returnOwnerACL && $current['accountId'] === $user['accountId']) {
                        continue;
                    }

                    $account_name = Array('accountId' => $user['accountId'],
                                         'accountLoginName' => $user['accountLoginName'],
                                         'accountDisplayName' => $user['accountDisplayName'],
                                         'accountFullName' => $user['accountFullName'],
                                         'accountFirstName' => $user['accountFirstName'],
                                         'accountLastName' => $user['accountLastName'],
                                         'contact_id' => $user['contact_id']);


                    $results[] = Array(
                        'account_name' => $account_name,
                        'account_id' => $user['accountLoginName'],
                        'readacl' => $readacl,
                        'writeacl' => $writeacl,
                        'sendacl' => $sendacl,
                        'administer' => $administer,
                    );
                }catch(Exception $e){

                }

            }
        }
        $return = Array('results' => $results, 'totalcount' => count($results));


        return $return;

    }

    public function getMyRights($_folder)
    {
        $response = $this->requestAndResponse("MYRIGHTS", array($this->escapeString($_folder)));
        
        return $response[0][2];
    }
    
     /**
     * get acls for a folder
     *
     * @param  array $box which folders to get the acls
     * @param  string $username with login name of current user
     * @return bool|array false if error, array with all users with sendas for this folder.
     * @throws Zend_Mail_Protocol_Exception
     * @TODO get the info from users not found in the getFullUserByLoginName
     */
    public function getUsersWithSendAsAcl($boxes)
    {
        $results = Array();
        foreach ($boxes as $box)
        {
            $this->sendRequest("MYRIGHTS", array($this->escapeString($box['globalname'])), $tag);

            $result = array();
            while (!$this->readLine($tokens, $tag))
            {
                $result = $tokens;
            }

            if ($tokens[0] != 'OK')
            {
                continue;
            }
            else
            {   

                if(stristr($result[2],'p')){
                    try
                        {
                            list(,$boxusername) = explode(Expressomail_Backend_Message::IMAPDELIMITER,$box['globalname']);
                            if($this->_useUidAsLogin()){
                                $aux = Tinebase_User::getInstance()->getFullUserByLoginName($boxusername)->toArray();
                            }else{
                                $aux = Tinebase_User::getInstance()->getFullUserByEmailAddress($boxusername.strstr(Tinebase_Core::getUser()->accountEmailAddress, '@'))->toArray();
                            }
                        }
                        catch (Tinebase_Exception_NotFound $e)
                        {
                            $aux = array();
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                                    . ' Error trying to get info from shared mailbox ' . $boxusername);
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__. ' ' . $e);
                        }
                        $results[] = $aux;
                }
            }
        }

        $return = Array('results' => $results, 'totalcount' => count($results));

        return $return;

    }


    /**
     * set acls for a folder
     *
     * @param  string $box which folder to get the acls
     * @param  array $acls the acls
     * @return bool|array false if error, array with all users and the acls of this user for this folder.
     * @throws Zend_Mail_Protocol_Exception
     */
    public function setFolderAcls($box, $acls)
    {

        $folderList = $this->listMailbox($box);
        $currentAcls = $this->getFolderAcls($box);
        $currentAcls = $currentAcls['results'];

        foreach($currentAcls as $index => $currentAcl){
            $find = false;
            foreach($acls as $acl){
                if($currentAcl['account_id'] == $acl['account_id']){
                    $find= true;
                    break 1;
                    }
            }
            if(!$find){
               $currentAcls[$index]['writeacl'] = false;
               $currentAcls[$index]['readacl'] = false;
               $currentAcls[$index]['sendacl'] = false;
               $acls[]=$currentAcls[$index];
            }
        }

        foreach($acls as $user){
            if(isset($user['account_data'])){
                $tmpUser =  Tinebase_User::getInstance()->getFullUserById($user['account_id'])->toArray();
                if($this->_useUidAsLogin()){
                    $login = $tmpUser['accountLoginName'];
                }else{
                    $login = $tmpUser['accountEmailAddress'];
                }
            }else{
                $tmpUser =  Tinebase_User::getInstance()->getFullUserById($user['account_name']['accountId'])->toArray();
                if($this->_useUidAsLogin()){
                    $login = $tmpUser['accountLoginName'];
                }else{
                    $login = $tmpUser['accountEmailAddress'];
                }
            }
            foreach($folderList as $folder => $value){
                if($user['sendacl']){
                    $setACL = $this->setACL($folder, $login, 'lrswikxtep');
                    }
                elseif($user['writeacl']){
                    $setACL = $this->setACL($folder, $login, 'lrswikxte');
                    }
                elseif($user['readacl']){
                    $setACL = $this->setACL($folder, $login, 'lrs');
                    }
                else{
                    $setACL = $this->setACL($folder, $login, '');

                    }
            }
        }
        sleep(3);
        return true;

    }

    /**
     * Examine and select have the same response. The common code for both
     * is in this method
     *
     * - overwritten to get UIDNEXT
     *
     * @param  string $command can be 'EXAMINE' or 'SELECT' and this is used as command
     * @param  string $box which folder to change to or examine
     * @return bool|array false if error, array with returned information
     *                    otherwise (flags, exists, recent, uidvalidity)
     */
    public function examineOrSelect($command = 'EXAMINE', $box = 'INBOX')
    {
        $this->sendRequest($command, array($this->escapeString($box)), $tag);
        
        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            if ($tokens[0] == 'FLAGS') {
                array_shift($tokens);
                $result['flags'] = $tokens;
                continue;
            }
            switch ($tokens[1]) {
                case 'EXISTS':
                case 'RECENT':
                    $result[strtolower($tokens[1])] = $tokens[0];
                    break;
                case '[UIDVALIDITY':
                    $result['uidvalidity'] = (int)$tokens[2];
                    break;
                case '[UIDNEXT':
                    $result['uidnext'] = (int)$tokens[2];
                    break;
                case '[UNSEEN':
                    $result['unseen'] = (int)$tokens[2];
                    break;
                default:
                    // ignore
            }
        }

        if ($tokens[0] != 'OK') {
            return false;
        }

        return $result;
    }

    /**
     * get status of a folder (unseen, recent, ...)
     *
     * @param  string $box which folder to change to or examine
     * @return bool|array false if error, array with returned information
     *                    otherwise (messages, recent, unseen)
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getFolderStatus($box = 'INBOX')
    {
        $command = "STATUS";
        $params = '(MESSAGES RECENT UNSEEN UIDVALIDITY)';
        $this->sendRequest($command, array($this->escapeString($box), $params), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            $result['messages'] = (isset($tokens[2][1])) ? (int)$tokens[2][1] : 0;
            $result['recent']   = (isset($tokens[2][3])) ? (int)$tokens[2][3] : 0;
            $result['uidvalidity']   = (isset($tokens[2][3])) ? (int)$tokens[2][3] : 0;
            $result['unseen']   = (isset($tokens[2][7])) ? (int)$tokens[2][7] : 0;
        }

        if ($tokens[0] != 'OK') {
            return false;
        }

        return $result;
    }

    /**
     * Open connection to IMAP server
     * - overwritten to adjust connection timeout (static late binding/timeout is defined as constant) :(
     *
     * @param  string      $host  hostname of IP address of POP3 server
     * @param  int|null    $port  of IMAP server, default is 143 (993 for ssl)
     * @param  string|bool $ssl   use 'SSL', 'TLS' or false
     * @return string welcome message
     * @throws Zend_Mail_Protocol_Exception
     *
     * @todo    can be removed when we can adjust the connection timeout in config
     */
    public function connect($host, $port = null, $ssl = false)
    {
        if ($ssl == 'SSL') {
            $host = 'ssl://' . $host;
        }

        if ($port === null) {
            $port = $ssl === 'SSL' ? 993 : 143;
        }

        $errno  =  0;
        $errstr = '';
        $this->_socket = @fsockopen($host, $port, $errno, $errstr, self::TIMEOUT_CONNECTION);
        if (!$this->_socket) {
            /**
             * @see Zend_Mail_Protocol_Exception
             */
            require_once 'Zend/Mail/Protocol/Exception.php';
            throw new Zend_Mail_Protocol_Exception('cannot connect to host : ' . $errno . ' : ' . $errstr);
        }

        if (!$this->_assumedNextLine('* OK')) {
            /**
             * @see Zend_Mail_Protocol_Exception
             */
            require_once 'Zend/Mail/Protocol/Exception.php';
            throw new Zend_Mail_Protocol_Exception('host doesn\'t allow connection');
        }

        if ($ssl === 'TLS') {
            $result = $this->requestAndResponse('STARTTLS');
            $result = $result && stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$result) {
                /**
                 * @see Zend_Mail_Protocol_Exception
                 */
                require_once 'Zend/Mail/Protocol/Exception.php';
                throw new Zend_Mail_Protocol_Exception('cannot enable TLS');
            }
        }
    }
    
    /**
     *
     * @param array $_params Parameters for authentication
     * @param string $_method Sasl method
     * @return array Response from server
     * @throws Exception 
     */
    public function saslAuthenticate($_params, $_method = 'PLAIN')
    {
        
        switch ($_method)
        {
            case 'PLAIN' :
                /*
                * $_params:
                * authcid = an identity associated with the authentication credentials
                * authzid = an identity to act as 
                * password = password for authcid identity
                */
                $authzid = isset($_params['authzid']) ? $_params['authzid'] : '';
                $authcid = isset($_params['authcid']) ? $_params['authcid'] : '';
                $password = isset($_params['password']) ? $_params['password'] : '';
                $auth = array(base64_encode($authzid.chr(0x00).$authcid.chr(0x00).$password));
                return $this->requestAndResponse("AUTHENTICATE $_method", $auth, true);
            default :
                throw new Exception("Sasl method $_method not implemented!");
        }
    }
        
    /**
     * Fetch Messages Ids changed since $modseq
     *
     * @param  string      $box   -  Folder to select
     * @param  integer     $modseq  -  $modSeq to search messages since
     * @return array       list of messages ids, flags changed since last modseq
     * @throws Zend_Mail_Protocol_Exception
     */
    public function fetchIdsChangedSinceModSeq($box, $modseq)
    {
        $this->sendRequest('SELECT', array($this->escapeString($box)), $tag0);
        $params = '1:* (FLAGS) (CHANGEDSINCE ' . $modseq .')';
        $this->sendRequest('FETCH', array($params), $tag);

        $result = array();
        $message = array();
        unset($ids);
        while (!$this->readLine($tokens, $tag, true)) {
            preg_match("/OK \[(\w+) (\d+)\] Ok/", $tokens, $matches);
            if ($matches[1] === 'HIGHESTMODSEQ') {
                $result['HIGHESTMODSEQ'] = (int)$matches[2];
            }

            preg_match("/(\d+) (\w+) \((\w+) \(([^)]+|(?))\) MODSEQ \((\d+)\)\)/", $tokens, $matches);
            if ($matches[2] === 'FETCH' && $matches[3] === 'FLAGS') {
                $message['MSGID'] = (int)$matches[1];
                $message['FLAGS'] = $matches[4];
                $result['messages'][$matches[1]] = $message;
                unset($message);
                if (isset($ids)) $ids = $ids . ',' . $matches[1];
                else $ids = $matches[1];
            }
        }
        
        preg_match("/(\w+) Completed/", $tokens, $matches);
        // last line has response code
        if ($matches[1] === 'OK' || $matches[1] === 'NO') {
            $result['STATUS'] = $matches[1];
        } else $result['STATUS'] = null;
        
        if (isset($ids)) {
            $params = $ids . ' UID';
            $this->sendRequest('FETCH', array($params), $tag2);
            while (!$this->readLine($tokens, $tag2, true)) {
                preg_match("/(\d+) (\w+) \(UID (\d+)\)/", $tokens, $matches);
                if ($matches[2] === 'FETCH') {
                    $result['messages'][$matches[1]]['UID'] = $matches[3]; 
                }
            }
        }
  
        return $result;
     }

     /**
     *
     * @return boolen
     */
    protected function _useUidAsLogin()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (isset($imapConfig['useEmailAsLoginName'])){
            return $imapConfig['useEmailAsLoginName'] === '1' ? FALSE : TRUE;
        } else {
            return TRUE;
        }
    }
}
