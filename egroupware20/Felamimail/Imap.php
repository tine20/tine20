<?php

/**
 * Zend_Mail_Storage_Imap
 */
require_once 'Zend/Mail/Storage/Imap.php';


/**
 * controller for Felamimail
 *
 * @package     FeLaMiMail
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Imap extends Zend_Mail_Storage_Imap
{
    /**
     * get root folder or given folder
     *
     * @param  string $reference mailbox reference for list
     * @param  string $mailbox   mailbox name match with wildcards
     * @return Zend_Mail_Storage_Folder root or wanted folder
     * @throws Zend_Mail_Storage_Exception
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getFolders($reference = '', $mailbox = '*')
    {
        $folders = $this->_protocol->listMailbox((string)$reference, $mailbox);
        if (!$folders) {
            throw new Zend_Mail_Storage_Exception('folder not found');
        }

        ksort($folders, SORT_STRING);
        
        $result = array();
        
        foreach ($folders as $globalName => $data) {
            $pos = strrpos($globalName, $data['delim']);
            if ($pos === false) {
                $localName = $globalName;
            } else {
                $localName = substr($globalName, $pos + 1);
            }
            if($data['flags']) {
                $selectable  = in_array('\\Noselect', $data['flags']) ? false : true;
                $hasChildren = in_array('\\haschildren', $data['flags']) ? true : false;
            }
            $folder = array(
                'localName'    => $localName,
                'globalName'   => $globalName,
                'delimiter'    => $data['delim'],
                'isSelectable' => $selectable,
                'hasChildren'  => $hasChildren
            );
            
            $result[$globalName] = $folder;
        }
        
        return $result;
    }
    
    public function getSummary($id)
    {
        $messages = array();

        $data = $this->_protocol->fetch(array('INTERNALDATE', 'RFC822.SIZE', 'UID', 'FLAGS', 'ENVELOPE'), $id);

        foreach($data as $messageInfo) {

            #var_dump($messageInfo);

            $flags = array();
            foreach ($messageInfo['FLAGS'] as $flag) {
                $flags[] = isset(self::$_knownFlags[$flag]) ? self::$_knownFlags[$flag] : $flag;
            }

            $message = array(
                'uid'           => $messageInfo['UID'],
                'flags'         => $flags,
                'received'      => $messageInfo['INTERNALDATE'],
                'size'          => $messageInfo['RFC822.SIZE'],
                'sent'          => $messageInfo['ENVELOPE'][0],
                'subject'       => $this->decodeMimeHeader($messageInfo['ENVELOPE'][1]),
                'from'          => $this->decodeMimeHeader($messageInfo['ENVELOPE'][2]),
                'sender'        => $this->decodeMimeHeader($messageInfo['ENVELOPE'][3]),
                'replyTo'       => $this->decodeMimeHeader($messageInfo['ENVELOPE'][4]),
                'to'            => $this->decodeMimeHeader($messageInfo['ENVELOPE'][5]),
                'cc'            => $this->decodeMimeHeader($messageInfo['ENVELOPE'][6]),
                'bcc'           => $this->decodeMimeHeader($messageInfo['ENVELOPE'][7]),
                'inReplyTo'     => $this->decodeMimeHeader($messageInfo['ENVELOPE'][8]),
                'messageId'     => $messageInfo['ENVELOPE'][9],

            );

            $messages[] = $message;
        }

        return $messages;
    }

    protected function decodeMimeHeader($header)
    {
        if(is_array($header)) {
            // from, to, cc, bcc, ...
            foreach($header as $key => $value) {
                foreach($value as $key2 => $value2) {
                    $value[$key2] = $this->decodeMimeHeader($value2);
                }
                $header[$key] = $value;
            }
        } elseif($header === 'NIL') {
            $header = NULL;
        } else {
            // just a string
            $header = iconv_mime_decode($header);
        }

        return $header;
    }
    
}