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
    
    public function testAddUserAdbContainer()
    {
        $container = $this->_getTestContainer(Addressbook_Config::APP_NAME, Addressbook_Model_Contact::class, true);

        $userToCreate = TestCase::getTestUser();
        $userToCreate->container_id = $container->getId();
        $pw = Tinebase_Record_Abstract::generateUID(12);

        $this->_usernamesToDelete[] = $userToCreate->accountLoginName;
        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);

        static::assertSame($container->getId(), $user->container_id);
    }
}
