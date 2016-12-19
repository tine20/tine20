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
 * @todo create an Account Map or reuse some Zend object
 * @todo organize the accountMap Code, put into a singleton class to use it globally????
 */

final class Expressomail_Backend_MessageComparator
{
    protected $_pagination;
    protected $_accountMap;

    public function __construct(Tinebase_Model_Pagination $_pagination)
    {
        $this->_pagination = $_pagination;
        $this->_accountMap = array();
    }

    protected function compareStrings($str1, $str2)
    {
        $str1 = Expressomail_Message::convertText($str1);
        $str2 = Expressomail_Message::convertText($str2);
        return ($this->_pagination->dir == 'ASC') ? strcasecmp($str1, $str2) : strcasecmp($str2, $str1);
    }

    protected function compareIntegers($intval1, $intval2)
    {
        return ($this->_pagination->dir == 'ASC') ? $intval1 - $intval2 :  $intval2 - $intval1;
    }

    protected function processSmimeValue($_structure)
    {
        switch ($_structure['contentType'])
        {
            case 'multipart/signed' :
                return 'signed-data';
            case 'application/x-pkcs7-mime':
            case 'application/pkcs7-mime':
                if (is_array($_structure['parameters']) && !empty($_structure['parameters']['smime-type']))
                {
                    return $_structure['parameters']['smime-type'];
                }
            default :
                return '';
        }
    }

    /**
     * Compare order of Expressomail_Model_Message acording to Tinebase_Model_Pagination
     * @param Expressomail_Model_Message $msg1
     * @param Expressomail_Model_Message $msg2
     * @return int
     *
     * @todo Convert int security value in Expressomail_Smime to corresponding string type
     */
    public function compare($msg1, $msg2)
    {
        switch ($this->_pagination->sort)
        {
            case 'received' : // Integer
                $value1 = $msg1[$this->_pagination->sort];
                $value2 = $msg2[$this->_pagination->sort];
            case 'sent' : // Integer
                $value1 = isset($value1) ? $value1 : $msg1['header']['date'];
                $value2 = isset($value2) ? $value2 : $msg2['header']['date'];
                $value1 = intval(Expressomail_Message::convertDate($value1)->format("U"));
                $value2 = intval(Expressomail_Message::convertDate($value2)->format("U"));
            case 'size' : // Integer
                $value1 = isset($value1) ? $value1 : intval($msg1[$this->_pagination->sort]);
                $value2 = isset($value2) ? $value2 : intval($msg2[$this->_pagination->sort]);
                return $this->compareIntegers($value1, $value2);

            case 'folder_id' : // Strings

                $folders = array();
                $translate = Tinebase_Translation::getTranslation('Expressomail');
                foreach (array($msg1, $msg2) as $msg)
                {
                    $folder = Expressomail_Backend_Folder::decodeFolderUid($msg[$this->_pagination->sort]);

                    // Optimization! Only create the account object once for every sort operation.
                    $account = (array_key_exists($folder['accountId'], $this->_accountMap)) ?
                        $this->_accountMap[$folder['accountId']] :
                        ($this->_accountMap[$folder['accountId']] =
                                Expressomail_Controller_Account::getInstance()->get($folder['accountId']));
                    $aux1 = explode('/',$folder['globalName']);
                    $aux2 = '';
                    foreach ($aux1 as $value)
                    {
                        $aux2 .= $translate->_($value) . '/';
                    }
                    $folders[] = $account->name . '/' . substr($aux2,0,strlen($aux2)-1);
                }

                list($value1, $value2) = $folders;

            //TODO: Should use a static method implemented on Model_Message or Expressomail_Smime
            case 'smime' :
                $value1 = isset($value1) ? $value1 : $this->processSmimeValue($msg1['structure']);
                $value2 = isset($value2) ? $value2 : $this->processSmimeValue($msg2['structure']);

            case 'flags' : // Strings
                if (!isset($value1))
                {
                    sort($msg1['flags']);
                    $value1 = implode(',', $msg1['flags']);
                }
                if (!isset($value2))
                {
                    sort($msg2['flags']);
                    $value2 = implode(',', $msg2['flags']);
                }
            case 'subject' : // Strings
                $value1 = isset($value1) ? $value1 : $msg1['header'][$this->_pagination->sort];
                $value2 = isset($value2) ? $value2 : $msg2['header'][$this->_pagination->sort];
            case 'id' : // Strings
                $value1 = isset($value1) ? $value1 : $msg1[$this->_pagination->sort];
                $value2 = isset($value2) ? $value2 : $msg2[$this->_pagination->sort];
                return $this->compareStrings($value1, $value2);

            case 'sender' :
            case 'to' :
            case 'from_name' :
            case 'from_email' : // Strings
                list($header,$field) = explode('_', $this->_pagination->sort);
                $field = empty($field) ? 'email' : $field;
                $address1 = Expressomail_Message::convertAddresses($msg1['header'][$header]);
                $address2 = Expressomail_Message::convertAddresses($msg2['header'][$header]);
                return $this->compareStrings((isset($address1[0]) && array_key_exists($field, $address1[0])) ? $address1[0][$field] : '',
                    (isset($address2[0]) && array_key_exists($field, $address2[0])) ? $address2[0][$field] : '');

        }
    }



}