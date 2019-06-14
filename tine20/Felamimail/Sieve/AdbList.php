<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to manage addressbook list shared email accounts sieve rules
 *
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_AdbList
{
    protected $_allowExternal = false;
    protected $_allowOnlyGroupMembers = false;
    protected $_keepCopy = false;
    protected $_receiverList = [];

    public function __toString()
    {
        $result = 'require ["envelope"];' . PHP_EOL;

        if ($this->_allowExternal) {
            $this->_addRecieverList($result);
            if (!$this->_keepCopy) {
                $result .= 'discard;' . PHP_EOL;
            }

        } else {
            if ($this->_allowOnlyGroupMembers) {
                $result .= 'if address :is :all "from" ["' . join('","', $this->_receiverList) . '"] {' . PHP_EOL;
            } else {
                // only internal email addresses are allowed to mail!
                // TODO FIX ME, get list of allowed domains!
                $result .= 'if address :is :domain "from" ["tine20.org"] {' . PHP_EOL;
            }

            $this->_addRecieverList($result);

            if (!$this->_keepCopy) {
                // we don't keep a copy, so discard everything
                $result .= '}' . PHP_EOL . 'discard;' . PHP_EOL;
            } else {
                // we keep msg by default, only if the condition was not met we discard?
                // always discard non-allowed msgs?!?
                $result .= '} else { discard; }' . PHP_EOL;
            }
        }



        return $result;
    }

    protected function _addRecieverList(&$result)
    {
        foreach ($this->_receiverList as $email) {
            $result .= 'redirect :copy ' . $email . ';' . PHP_EOL;
        }
    }

    /**
     * @param Addressbook_Model_List $_list
     * @return Felamimail_Sieve_AdbList
     */
    static public function createFromList(Addressbook_Model_List $_list)
    {
        $sieveRule = new self();

        if (empty($_list->members) || empty($_list->email)) {
            return $sieveRule;
        }

        $sieveRule->_receiverList = array_keys(Addressbook_Controller_Contact::getInstance()->search(
            new Addressbook_Model_ContactFilter([
                ['field' => 'id', 'operator' => 'in', 'value' => $_list->members]
            ]), null, false, ['email']));

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY]) {
            $sieveRule->_keepCopy = true;
        }

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_EXTERNAL]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_EXTERNAL]) {
            $sieveRule->_allowExternal = true;
        }

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_ONLY_MEMBERS]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_ONLY_MEMBERS]) {
            if ($sieveRule->_allowExternal) {
                throw new Tinebase_Exception_UnexpectedValue('can not combine allowExternal and allowOnlyMembers');
            }
            $sieveRule->_allowOnlyGroupMembers = true;
        }

        return $sieveRule;
    }
}