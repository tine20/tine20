<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_Acl_Roles
 */
class Admin_Acl_RightsTest extends TestCase
{
    /**
     * try to check getting application rights
     *
     */   
    public function testGetAllApplicationRights()
    {
        $rights = Admin_Acl_Rights::getInstance()->getAllApplicationRights();
        
        $this->assertGreaterThan(0, count($rights));
    } 
    
    /**
     * try to check getting application rights
     */   
    public function testGetTranslatedRightDescriptions()
    {
        $all = Admin_Acl_Rights::getTranslatedRightDescriptions();
        $text = $all[Admin_Acl_Rights::MANAGE_ROLES];
        
        $this->assertNotEquals('', $text['text']);
        $this->assertNotEquals('', $text['description']);
        $this->assertNotEquals(Admin_Acl_Rights::MANAGE_ROLES . ' right', $text['description']);
        
        $translate = Tinebase_Translation::getTranslation('Admin');
        $this->assertEquals($translate->_('Manage roles'), $text['text']);
    } 
}
