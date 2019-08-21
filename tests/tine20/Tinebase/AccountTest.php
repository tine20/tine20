<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_User
 * @deprecated, some fns might be moved to other testclasses
 */
class Tinebase_AccountTest extends TestCase
{
    /**
     * try to add an account
     * @return Tinebase_Model_FullUser
     */
    public function testAddAccount()
    {
        $this->_usernamesToDelete[] = 'tine20phpunit';
        $account = Tinebase_User::getInstance()->addUser(new Tinebase_Model_FullUser(
            array(
                'accountLoginName'    => 'tine20phpunit',
                'accountStatus'       => Tinebase_User::STATUS_ENABLED,
                'accountExpires'      => null,
                'accountPrimaryGroup' => Tinebase_Core::getUser()->accountPrimaryGroup,
                'accountLastName'     => 'Tine 2.0',
                'accountFirstName'    => 'PHPUnit',
                'accountEmailAddress' => 'phpunit@' . TestServer::getPrimaryMailDomain()
            )
        ));
        
        $this->assertNotEmpty($account->accountId);
        $this->assertEquals(0, $account->is_deleted);

        return $account;
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetAccounts()
    {
        $this->testAddAccount();
        
        $accounts = Tinebase_User::getInstance()->getUsers('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));

        // test with sort dir
        $accounts = Tinebase_User::getInstance()->getFullUsers('phpunit', 'accountStatus', 'DESC');
        
        $this->assertEquals(1, count($accounts));
    }

    /**
     * try to get all full accounts containing phpunit in there name
     *
     */
    public function testGetFullAccounts()
    {
        $this->testAddAccount();
        
        $accounts = Tinebase_User::getInstance()->getFullUsers('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));
        
        // test with sort dir
        $accounts = Tinebase_User::getInstance()->getFullUsers('phpunit', 'accountStatus', 'ASC');
        
        $this->assertEquals(1, count($accounts));
    }
 
    /**
     * try to get the account with the loginName tine20phpunit
     *
     */
    public function testGetAccountByLoginName()
    {
        $account = $this->testAddAccount();
        
        $testAccount = Tinebase_User::getInstance()->getUserByLoginName(
            $account->accountLoginName
        );
        
        // Tinebase_Model_User has no accountLoginName
        $this->assertEquals($account->accountId, $testAccount->accountId);
    }
    
    /**
     * try to get the full account with the loginName tine20phpunit
     *
     */
    public function testGetFullAccountByLoginName()
    {
        $account = $this->testAddAccount();
        
        $testAccount = Tinebase_User::getInstance()->getFullUserByLoginName(
            $account->accountLoginName
        );
        
        $this->assertEquals($account->accountId, $testAccount->accountId);
    }

    /**
     * try to get the account with the id 10
     *
     */
    public function testGetAccountById()
    {
        $account = $this->testAddAccount();
        
        $testAccount = Tinebase_User::getInstance()->getUserById(
            $account->accountId
        );
        
        $this->assertEquals($account->accountId, $testAccount->accountId);
    }

    /**
     * try to get the full account with the id 10
     *
     */
    public function testGetFullAccountById()
    {
        $account = $this->testAddAccount();
        
        $testAccount = Tinebase_User::getInstance()->getFullUserById(
            $account->accountId
        );
        
        $this->assertEquals($testAccount->accountId, $account->accountId);
    }
    
    /**
     * try to update an account
     *
     */
    public function testUpdateAccount()
    {
        $account = $this->testAddAccount();

        $account->accountLoginName = 'tine20phpunit-updated';
        $account->accountStatus    = Tinebase_User::STATUS_DISABLED;
        $account->accountLastName  = 'Tine 2.0 Updated';
        $account->accountFirstName = 'PHPUnit Updated';

        $this->_usernamesToDelete[] = $account->accountLoginName;
        $testAccount = Tinebase_User::getInstance()->updateUser($account);
        
        $this->assertEquals('tine20phpunit-updated',        $testAccount->accountLoginName);
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $testAccount->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatusEnabled()
    {
        // prepare account
        $account = $this->testAddAccount();
        
        $account->accountStatus = Tinebase_User::STATUS_DISABLED;
        
        $account = Tinebase_User::getInstance()->updateUser($account);
        
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $account->accountStatus);
        
        // test account
        Tinebase_User::getInstance()->setStatus($account, Tinebase_User::STATUS_ENABLED);
        
        $account = Tinebase_User::getInstance()->getFullUserById($account);
        
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $account->accountStatus);
    }
    
    /**
     * try to disable an account
     *
     */
    public function testSetStatusDisabled()
    {
        // prepare account
        $account = $this->testAddAccount();
        
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $account->accountStatus);
        
        // test account
        Tinebase_User::getInstance()->setStatus($account, Tinebase_User::STATUS_DISABLED);

        $account = Tinebase_User::getInstance()->getFullUserById($account);
        
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $account->accountStatus);
    }
    
    /**
     * try to update the logintimestamp
     *
     * @todo    check if set correctly
     */
    public function testSetLoginTime()
    {
        $account = $this->testAddAccount();
        
        Tinebase_User::getInstance()->setLoginTime($account, '127.0.0.1');
        
        $updatedAccount = Tinebase_User::getInstance()->getFullUserById($account);
        
        $this->assertNotEquals($account->accountLastLogin, $updatedAccount->accountLastLogin);
    }
    
    /**
     * try to set the expirydate
     *
     * @todo    check if set correctly
     */
    public function testSetExpiryDate()
    {
        $account = $this->testAddAccount();
        
        Tinebase_User::getInstance()->setExpiryDate($account, Tinebase_DateTime::now());
        
        $updatedAccount = Tinebase_User::getInstance()->getFullUserById($account);
        
        $this->assertNotEquals($account->accountExpires, $updatedAccount->accountExpires);
    }
    
    /**
     * try to delete an account
     *
     */
    public function testDeleteAccount()
    {
        $account = $this->testAddAccount();

        Tinebase_User::getInstance()->deleteUser($account);

        $this->setExpectedException('Tinebase_Exception_NotFound');

        $account = Tinebase_User::getInstance()->getUserById($account, 'Tinebase_Model_FullUser');
    }

    /**
     * try to delete multiple accounts
     *
     */
    public function testDeleteAccounts()
    {
        $account = $this->testAddAccount();

        $todelete = array($account->accountId);

        Tinebase_User::getInstance()->deleteUsers($todelete);

        $this->setExpectedException('Tinebase_Exception_NotFound');

        Tinebase_User::getInstance()->getUserById($account, 'Tinebase_Model_FullUser');
    }

    /**
     * try to convert account id and check if correct exceptions are thrown 
     *
     */
    public function testConvertAccountIdToInt()
    {
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        
        Tinebase_Model_User::convertUserIdToInt(0);
    }
    
    /**
     * try to convert id of account object and check if correct exceptions are thrown 
     *
     */
    public function testConvertAccountIdToIntWithAccount()
    {
        $noIdAccount = new Tinebase_Model_FullUser(
            array(
                'accountLoginName'    => 'tine20phpunit-noid',
                'accountStatus'       => Tinebase_User::STATUS_DISABLED,
                'accountExpires'      => null,
                'accountPrimaryGroup' => Tinebase_Core::getUser()->accountPrimaryGroup,
                'accountLastName'     => 'Tine 2.0 noid',
                'accountFirstName'    => 'PHPUnit noid',
                'accountEmailAddress' => 'phpunit@' . TestServer::getPrimaryMailDomain(),
            )
        );
        
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        
        Tinebase_Model_User::convertUserIdToInt($noIdAccount);
    }
}
