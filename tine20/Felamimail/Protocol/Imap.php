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
        } else if ($to === INF) {
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
            $set .= ':' . ($to == INF ? '*' : (int)$to);
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
}