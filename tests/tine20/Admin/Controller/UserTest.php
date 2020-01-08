<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Admin_Controller_User
 */
class Admin_Controller_UserTest extends TestCase
{
    public function testAddUserWithAlreadyExistingEmailData()
    {
        $this->_skipIfLDAPBackend();

        $userToCreate = TestCase::getTestUser([
            'accountLoginName'      => 'phpunitadminjson',
            'accountEmailAddress'   => 'phpunitadminjson@' . TestServer::getPrimaryMailDomain(),
        ]);
        $userToCreate->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailAddress'     => $userToCreate->accountEmailAddress,
        ));
        $pw = Tinebase_Record_Abstract::generateUID(12);
        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
        // remove user from tine20 table and add again
        $backend = new Tinebase_User_Sql();
        $backend->deleteUserInSqlBackend($user);

        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
        $this->_usernamesToDelete[] = $user->accountLoginName;
        self::assertEquals($user->accountEmailAddress, $userToCreate->accountEmailAddress);
    }

    public function testAddAccountWithEmailUserXprops()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

        $xpropsConf = Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS};
        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = true;

        // create user + check if email user is created
        $user = $this->_createUserWithEmailAccount();
        self::assertTrue(isset($user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP]),
            'email userid xprop missing: ' . print_r($user->toArray(), true));
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        $xpropsUser = clone($user);
        Tinebase_EmailUser_XpropsFacade::setIdFromXprops($user, $xpropsUser);
        $userInBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertEquals($user->accountEmailAddress, $userInBackend['email'], 'email was not added: '
            . print_r($userInBackend, true));
        self::assertEquals($user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP], $userInBackend['userid']);

        // update user (email address) + check if email user is updated
        $newEmail = 'newaddress' . Tinebase_Record_Abstract::generateUID(6)
            . '@' . TestServer::getPrimaryMailDomain();
        $user->accountEmailAddress = $newEmail;
        Admin_Controller_User::getInstance()->update($user);
        $userInBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertEquals($newEmail, $userInBackend['email'], 'email was not updated: '
            . print_r($userInBackend, true));

        // delete user + check if email user is deleted
        Admin_Controller_User::getInstance()->delete([$user->getId()]);
        $userInBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertEmpty($userInBackend['email'], 'email user should be deleted: '
            . print_r($userInBackend, true));

        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = $xpropsConf;
    }
}
