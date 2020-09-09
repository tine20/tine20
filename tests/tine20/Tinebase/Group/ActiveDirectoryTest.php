<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group_ActiveDirectory
 */
class Tinebase_Group_ActiveDirectoryTest extends PHPUnit_Framework_TestCase
{
    protected $baseDN     = 'dc=example,dc=com';
    protected $groupsDN   = 'cn=users,dc=example,dc=com';
    protected $userDN     = 'cn=users,dc=example,dc=com';
    protected $domainSid  = 'S-1-5-21-2127521184-1604012920-1887927527';
    protected $userSid    = 'S-1-5-21-2127521184-1604012920-1887927527-72713';
    protected $groupSid   = 'S-1-5-21-2127521184-1604012920-1887927527-62713';
    protected $groupObjectGUID = '0cbadcc5-72f7-4a2d-8858-7ba6c80e6c15';
    protected $userObjectGUID  = '0cbadcc5-72f7-4a2d-8858-7ba6c80e6c16';
    protected $groupBaseFilter = 'objectclass=group';
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_groupAD = new Tinebase_Group_ActiveDirectory(array(
            'userDn'     => $this->userDN,
            'groupsDn'   => $this->groupsDN,
            'ldap'       => $this->_getTinebaseLdapStub(),
            'useRfc2307' => true
        )); 
    }

    /**
     * try to add a group
     *
     */
    public function testResolveUUIdToGIdNumber()
    {
        $primaryGroupNumber = $this->_groupAD->resolveUUIdToGIdNumber($this->groupObjectGUID);
        
        $this->assertEquals(62713, $primaryGroupNumber);
    }
    
    public function testAddGroupInSyncBackend()
    {
        $this->markTestIncomplete('must implement iterator mock in _getZendLdapCollectionStub');
        
        $testGroup = $this->_groupAD->addGroupInSyncBackend(new Tinebase_Model_Group(array(
            'name' => 'PHPUnit Test Group'
        )));
    }
    
    /**
     * 
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getTinebaseLdapStub()
    {
        if (PHP_VERSION_ID >= 70400) {
            self::markTestSkipped('FIXME not working with php7.4 (Function ReflectionType::__toString() is deprecated)');
        }

        $stub = $this->getMockBuilder('Tinebase_Ldap')
                     ->disableOriginalConstructor()
                     ->getMock();
        
        $stub->expects($this->any())
             ->method('getFirstNamingContext')
             ->will($this->returnValue($this->baseDN));
        
        // Configure the search method
        $stub->expects($this->any())
             ->method('search')
             ->will($this->returnCallback(array($this, '_stubSearchCallback')));

        return $stub;
    }
    
    public function _stubSearchCallback($filter, $basedn = null, $scope = self::SEARCH_SCOPE_SUB, array $attributes = array(), $sort = null, $collectionClass = null)
    {
        switch (base64_encode((string) $filter)) {
            #case 'objectClass=domain':
            case 'b2JqZWN0Q2xhc3M9ZG9tYWlu':
                return $this->_getZendLdapCollectionStub(array('objectsid' => array($this->domainSid), 'distinguishedname' => array('DC=tine20,DC=org')));
                
                break;
                
            #case "(&(objectclass=group)(objectguid=$this->groupObjectGUID))":
            case 'KCYob2JqZWN0Y2xhc3M9Z3JvdXApKG9iamVjdGd1aWQ9xdy6XDBj93ItSohYe6bIXDBlbFwxNSkp':
                return $this->_getZendLdapCollectionStub(array('objectsid' => array($this->groupSid)));
                
                break;
            
            default:
                $this->fail("unkown filter $filter in " . __METHOD__);
                
                break;
        }
    }
    
    protected function _getZendLdapCollectionStub($data)
    {
        $stub = $this->getMockBuilder('Zend_Ldap_Collection')
             ->disableOriginalConstructor()
             ->getMock();
        
        $stub->expects($this->any())
             ->method('getFirst')
             ->will($this->returnValue($data));
        
        return $stub;
    }
}
