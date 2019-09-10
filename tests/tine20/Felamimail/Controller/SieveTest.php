<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Controller_Sieve
 */
class Felamimail_Controller_SieveTest extends Felamimail_TestCase
{
    public function testAdbMailinglistPutSieveRule()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist();
        $result = Felamimail_Sieve_AdbList::setScriptForList($mailinglist);
        self::assertTrue($result);
    }

    public function testAdbMailinglistSieveRuleForward()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist();

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);

        self::assertContains('require ["envelope","copy","reject"];', $script->getSieve());
        self::assertContains('if address :is :domain "from" ["' . TestServer::getPrimaryMailDomain() . '"] {
redirect :copy "' . Tinebase_Core::getUser()->accountEmailAddress . '";
} else { reject', $script->getSieve());

        // TODO make it work (our sieve testsetup is not ready for this)
        return true;

        // write mail to list & check if user receives mail
        $subject = 'sieve list forward test message ' . Tinebase_Record_Abstract::generateUID(10);
        $message = new Felamimail_Model_Message(array(
            'account_id'    => $this->_account->getId(),
            'subject'       => $subject,
            'to'            => array($mailinglist->email),
            'body'          => 'aaaaaä <br>',
        ));

        Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);

        $result = $this->_getMessages('INBOX', [
            ['field' => 'subject', 'operator' => 'equals', 'value' => $subject]
        ]);
        self::assertEquals(1, $result['totalcount'], print_r($result, true));
    }

    public function testAdbMailinglistSieveRuleCopy()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist([
            Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY
        ]);

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);
        self::assertContains('if address :is :domain "from" ["' . TestServer::getPrimaryMailDomain() . '"] {
redirect :copy "' . Tinebase_Core::getUser()->accountEmailAddress . '";
} else { reject "', $script->getSieve());

        // TODO check sieve script functionality
    }

    public function testAdbMailinglistSieveRuleForwardExternal()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist([
            Addressbook_Model_List::XPROP_SIEVE_ALLOW_EXTERNAL
        ]);

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);
        self::assertNotContains('if address :is :domain "from" ["' . TestServer::getPrimaryMailDomain() . '"]', $script->getSieve());

        // TODO check sieve script functionality
    }

    public function testAdbMailinglistSieveRuleForwardOnlyMembers()
    {
        $this->_testNeedsTransaction();

        $mailinglist = $this->_createMailinglist([
            Addressbook_Model_List::XPROP_SIEVE_ALLOW_ONLY_MEMBERS
        ]);

        // check if sieve script is on sieve server
        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($mailinglist);
        self::assertContains('if address :is :all "from" ["' . $this->_originalTestUser->accountEmailAddress . '"]', $script->getSieve());
        self::assertContains('reject "Your email has been rejected"', $script->getSieve());

        // TODO check sieve script functionality
    }
}
