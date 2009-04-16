<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * message model for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Model
 */
class Felamimail_Model_Message extends Zend_Mail_Message 
{    
    /**
     * Public constructor
     *
     * In addition to the parameters of Zend_Mail_Message::__construct() this constructor supports:
     * - uid  use UID FETCH if ftru
     *
     * @param  array $params  list of parameters
     * @throws Zend_Mail_Exception
     */
    public function __construct(array $params)
    {
        if (isset($params['uid'])) {
            $this->_useUid = (bool)$params['uid'];
        }
        
        parent::__construct($params);
    }
    
    public function getBody($_contentType)
    {
        if($this->isMultipart()) {
            foreach (new RecursiveIteratorIterator($this) as $messagePart) {
                $contentType    = $messagePart->getHeaderField('content-type', 0);
                if($contentType == $_contentType) {
                    $part = $messagePart;
                    break;
                }
            }
        } else {
            $part = $this;
        }
        
        $content = $part->getContent();
        
        try {
            $encoding       = $part->getHeaderField('content-transfer-encoding');
        } catch(Zend_Mail_Exception $e) {
            $encoding       = null;
        }
        
        switch (strtolower($encoding)) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $content = quoted_printable_decode($content);
                break;
            case Zend_Mime::ENCODING_BASE64:
                $content = base64_decode($content);
                break;
        }
        
        
        try {
            $charset        = $part->getHeaderField('content-type', 'charset');
        } catch(Zend_Mail_Exception $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no charset header found. assume iso-8859-1");
            $charset        = 'iso-8859-1';
        }
        
        if(strtolower($charset) != 'utf-8') {
            $content = iconv($charset, 'utf-8', $content);
        }
        
        return $content;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $_adressList
     * @return array
     */
    public static function parseAdresslist($_addressList)
    {
        $addresses = array();
        $inParenthesis = false;
        
        for($i = 0; $i < strlen($_addressList); $i++) {
            switch($_addressList[$i]) {
                case '"':
                    $address .= $_addressList[$i];
                    $inParenthesis = !$inParenthesis;
                    
                    break;
                    
                case ',':
                    if($inParenthesis) {
                        $address .= $_addressList[$i];
                    } else {
                        $addresses[] = trim($address);
                        $address = '';
                    }
                    
                    break;
                    
                default:
                    $address .= $_addressList[$i];
                    
                    break;
            }
        }

        $address = trim($address);
        if(!empty($address)) {
            $addresses[] = $address;
        }
        
        foreach($addresses as $key => $address) {
            if(preg_match('/"(.*)<(.+@[^@]+)>/', $address, $matches)) {
                $addresses[$key] = array('name' => trim(trim($matches[1]), '"'), 'address' => trim($matches[2]));
            } else {
                $addresses[$key] = array('name' => null, 'address' => $address);
            }
        }

        return $addresses;
    }
}
