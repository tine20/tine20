<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_EmailUser_Factory
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 */
class Tinebase_EmailUser_FactoryTest extends TestCase
{
    /**
     * 
     */
    public function testGetMailApplicationName()
    {
        $name = Tinebase_EmailUser_Factory::getMailApplicationName();
        
        $this->assertNotEmpty($name);
    }
    
    /**
     * 
     */
    public function testGetInstance()
    {
        $instance = Tinebase_EmailUser_Factory::getInstance('Controller_Message');
        
        $this->assertInstanceOf('Tinebase_Controller_Record_Abstract', $instance);
    }
}
