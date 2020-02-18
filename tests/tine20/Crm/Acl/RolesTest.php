<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * Test class for Tinebase_Acl_Roles
 */
class Crm_Acl_RolesTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->objects['application'] = Tinebase_Application::getInstance()->getApplicationByName('Crm');
        $this->objects['user'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'crmtine20phpunit',
            'accountDisplayName'    => 'crmtine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'crmPHPUnit',
            'accountEmailAddress'   => 'crmphpunit@' . TestServer::getPrimaryMailDomain(),
        ));
        $this->objects['role'] = new Tinebase_Model_Role(array(
            'id'                    => '10',
            'name'                  => 'phpunitrole',
            'description'           => 'test role for phpunit',
        ));
        $this->objects['role_2'] = new Tinebase_Model_Role(array(
            'id'                    => '11',
            'name'                  => 'phpunitrole 2',
            'description'           => 'test role 2 for phpunit',
        ));
        
        // add account for group / role member tests
        $this->objects['user'] = Tinebase_User::getInstance()->addUser($this->objects['user']);
        Tinebase_Group::getInstance()->addGroupMember($this->objects['user']->accountPrimaryGroup, $this->objects['user']);

        return;
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_User::getInstance()->deleteUser($this->objects['user']->accountId);
        
        parent::tearDown();
    }

    /**
     * try to add a role
     *
     */
    public function testCreateRole()
    {
        $role = Tinebase_Acl_Roles::getInstance()->createRole($this->objects['role']);
        
        $this->assertEquals($role->getId(), $this->objects['role']->getId());
    }
    
    /**
     * try to add a role membership
     *
     */
    public function testSetRoleMember()
    {
        $this->testCreateRole();
        
        $member = array(
            array(
                "type"  => 'user',
                "id"    => $this->objects['user']->getId(),
            )
        );
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($this->objects['role']->getId(), $member);
        
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($this->objects['role']->getId());
        
        $this->assertGreaterThan(0, count($members));
    }
    
    /**
     * try to remove member from role
     */
    public function removeRoleMember()
    {
        $this->testCreateRole();
        
        Tinebase_Acl_Roles::getInstance()->removeRoleMember(10, $this->objects['user']);
        
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($this->objects['role']->getId());
        
        $this->assertEquals(0, count($members));
    }
    
    /**
     * try to add member to role
     */
    public function testAddRoleMember()
    {
        $this->testCreateRole();
        
        Tinebase_Acl_Roles::getInstance()->addRoleMember($this->objects['role']->getId(), array(
            'type'     => 'user',
            'id'    => $this->objects['user']->getId()
        ));
        
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($this->objects['role']->getId());
        
        $this->assertGreaterThan(0, count($members));
    }
        
    /**
     * try to add member to multiple roles
     */
    public function testSetRoleMemberships()
    {
        $this->testCreateRole();
        
        // create role 2 for test
        $role2 = Tinebase_Acl_Roles::getInstance()->createRole($this->objects['role_2']);
        
        // remove role members
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($this->objects['role']->getId(), array());
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($this->objects['role_2']->getId(), array());
        
        Tinebase_Acl_Roles::getInstance()->setRoleMemberships(
            array(
                'type'     => 'user',
                'id'     => $this->objects['user']->getId()
            ),
            array(
                $this->objects['role']->getId(),
                $this->objects['role_2']->getId()
            )
        );
        
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($this->objects['role']->getId());
        $members_2 = Tinebase_Acl_Roles::getInstance()->getRoleMembers($this->objects['role_2']->getId());
        
        $this->assertEquals(2, (count($members) + count($members_2)));
    }
    
    /**
     * try to add a role right
     */
    public function testSetRoleRight()
    {
        $this->testCreateRole();
        
        $right = array(
            array(
                "application_id"    => $this->objects['application']->getId(),
                "right"             => Tinebase_Acl_Rights::RUN,
            )
        );
        Tinebase_Acl_Roles::getInstance()->setRoleRights($this->objects['role']->getId(), $right);
        
        $rights = Tinebase_Acl_Roles::getInstance()->getRoleRights($this->objects['role']->getId());
        
        $this->assertGreaterThan(0, count($rights));
    }
    
    /**
     * try to check getting applications
     */
    public function testGetApplications()
    {
        $result = Tinebase_Acl_Roles::getInstance()->getApplications($this->objects['user']->getId());
        
        $this->assertGreaterThan(0, count($result->toArray()));
    }

    /**
     * try to check getting applications
     */
    public function testGetApplicationRights()
    {
        $result = Tinebase_Acl_Roles::getInstance()->getApplicationRights(
            $this->objects['application']->name,
            $this->objects['user']->getId()
        );
        
        $this->assertGreaterThan(0, count($result));
    }
    
    /**
     * try to check if user with a role has right
     */
    public function testHasRight()
    {
        $result = Tinebase_Acl_Roles::getInstance()->hasRight(
            $this->objects['application']->name,
            $this->objects['user']->getId(),
            Tinebase_Acl_Rights::RUN
        );
        
        $this->assertTrue($result, 'has no run right');
        
        $result = Tinebase_Acl_Roles::getInstance()->hasRight(
            $this->objects['application']->name,
            $this->objects['user']->getId(),
            Tinebase_Acl_Rights::ADMIN
        );

        $this->assertFalse($result, 'has admin right for application ' . $this->objects['application']->name);
        
        // test for not installed application
        $result = Tinebase_Acl_Roles::getInstance()->hasRight(
            'FooBar',
            $this->objects['user']->getId(),
            Tinebase_Acl_Rights::ADMIN
        );

        $this->assertFalse($result, 'user has admin right for not installed application');
    }

    /**
     * try to delete a role
     *
     */
    public function testDeleteRole()
    {
        $this->testSetRoleMemberships();
        
        // remove role members and rights first
        Tinebase_Acl_Roles::getInstance()->setRoleRights($this->objects['role']->getId(), array());
        
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($this->objects['role']->getId(), array());
        Tinebase_Acl_Roles::getInstance()->setRoleMembers($this->objects['role_2']->getId(), array());
        
        Tinebase_Acl_Roles::getInstance()->deleteRoles(array($this->objects['role']->getId(), $this->objects['role_2']->getId()));
      
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tinebase_Acl_Roles::getInstance()->getRoleById($this->objects['role']->getId());
    }
}
