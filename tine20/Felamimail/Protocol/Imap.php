<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Protocol
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Felamimail IMAP backend
 *
 * @package     Felamimail
 * @subpackage  Protocol
 */
class Felamimail_Protocol_Imap extends Zend_Mail_Protocol_Imap
{
    /**
     * timeout in seconds for initiating session (parent: 30)
     */
    const TIMEOUT_CONNECTION = 20;
    
    /**
     * fetch one or more items of one or more messages
     *
     * @param  string|array $items items to fetch from message(s) as string (if only one item)
     *                             or array of strings
     * @param  int          $from  message for items or start message if $to !== null
     * @param  int|null     $to    if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message avaible
     * @return string|array if only one item of one message is fetched it's returned as string
     *                      if items of one message are fetched it's returned as (name => value)
     *                      if one items of messages are fetched it's returned as (msgno => value)
     *                      if items of messages are fetchted it's returned as (msgno => (name => value))
     * @throws Zend_Mail_Protocol_Exception
     */
    public function fetch($items, $from, $to = null, $uid = false)
    {
        if (is_array($from)) {
            $set = implode(',', $from);
        } else if ($to === null) {
            $set = (int)$from;
        } else if (is_infinite($to)) {
            $set = (int)$from . ':*';
        } else {
            $set = (int)$from . ':' . (int)$to;
        }

        $items = (array)$items;
        $itemList = $this->escapeList($items);
        
        $this->sendRequest($uid ? 'UID FETCH' : 'FETCH', array($set, $itemList), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            // ignore other responses
            if ($tokens[1] != 'FETCH') {
                continue;
            }
            // ignore other messages
            #if ($to === null && !is_array($from) && $tokens[0] != $from) {
            #    continue;
            #}
            $data = array();
            while (key($tokens[2]) !== null) {
                $data[current($tokens[2])] = next($tokens[2]);
                next($tokens[2]);
            }

            // if we want only one message we can ignore everything else and just return
            if ($to === null && !is_array($from) && (($uid !== true && $tokens[0] == $from) || ($uid === true && $data['UID'] == $from))) {
                // we still need to read all lines
                while (!$this->readLine($tokens, $tag));
                // if we only want one item we return that one directly
                if (count($items) == 1) {
                    $data = $data[$items[0]];
                }
                return $data;
            }
            
            $messageId = $uid === true ? $data['UID'] : $tokens[0];
            if (count($items) == 1) {
                $data = $data[$items[0]];
            }
            $result[$messageId] = $data;
        }

        if ($to === null && !is_array($from)) {
            /**
             * @see Zend_Mail_Protocol_Exception
             */
            require_once 'Zend/Mail/Protocol/Exception.php';
            throw new Zend_Mail_Protocol_Exception('the single id was not found in response');
        }

        return $result;
    }
    
    /**
     * set flags
     *
     * @param  array       $flags  flags to set, add or remove - see $mode
     * @param  int         $from   message for items or start message if $to !== null
     * @param  int|null    $to     if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message avaible
     * @param  string|null $mode   '+' to add flags, '-' to remove flags, everything else sets the flags as given
     * @param  bool        $silent if false the return values are the new flags for the wanted messages
     * @return bool|array new flags if $silent is false, else true or false depending on success
     * @throws Zend_Mail_Protocol_Exception
     */
    public function store(array $flags, $from, $to = null, $mode = null, $silent = true, $uid = false)
    {
        $item = 'FLAGS';
        if ($mode == '+' || $mode == '-') {
            $item = $mode . $item;
        }
        if ($silent) {
            $item .= '.SILENT';
        }

        $flags = $this->escapeList($flags);
        $set = (int)$from;
        if ($to != null) {
            $set .= ':' . (is_infinite($to) ? '*' : (int)$to);
        }

        $result = $this->requestAndResponse($uid ? 'UID STORE' : 'STORE', array($set, $item, $flags), $silent);

        if ($silent) {
            return $result ? true : false;
        }

        $tokens = $result;
        $result = array();
        foreach ($tokens as $token) {
            if ($token[1] != 'FETCH' || $token[2][0] != 'FLAGS') {
                continue;
            }
            $result[$token[0]] = $token[2][1];
        }

        return $result;
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
     * get server namespace
     * 
     * @return bool|array false if error, array with returned namespace information
     */
    public function getNamespace()
    {
        $this->sendRequest('NAMESPACE', array(), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($tokens, TRUE));
            
            $nsNames = array('personal', 'other', 'shared');
            $index = 0;
            
            foreach ($tokens as $token) {
                if (is_array($token)) {
                    $result[$nsNames[$index]] = array(
                        'name' => preg_replace('/"/', '', $token[0][0]), 
                        'delimiter' => preg_replace('/"/', '', $token[0][1]),
                    );
                } else if ($token == 'NIL') {
                    $result[$nsNames[$index]] = array('name' => 'NIL');
                } else {
                    continue;
                }
                $index++;
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
        $params = '(MESSAGES RECENT UNSEEN)';
        $this->sendRequest($command, array($this->escapeString($box), $params), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            $result['messages'] = (isset($tokens[2][1])) ? (int)$tokens[2][1] : 0;
            $result['recent']   = (isset($tokens[2][3])) ? (int)$tokens[2][3] : 0;
            $result['unseen']   = (isset($tokens[2][5])) ? (int)$tokens[2][5] : 0;
        }

        if ($tokens[0] != 'OK') {
            return false;
        }
        
        return $result;
    }
    
    /**
     * copy message set from current folder to other folder
     *
     * - overwritten to use UID COPY
     * 
     * @param string   $folder destination folder
     * @param int|null $to     if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message avaible
     * @return bool success
     * @throws Zend_Mail_Protocol_Exception
     */
    public function copy($folder, $from, $to = null, $uid = false)
    {
        $set = (int)$from;
        if ($to != null) {
            $set .= ':' . (is_infinite($to) ? '*' : (int)$to);
        }

        return $this->requestAndResponse($uid ? 'UID COPY' : 'COPY', array($set, $this->escapeString($folder)), true);
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
}
