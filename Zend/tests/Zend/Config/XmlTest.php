<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Config
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Zend_Config_Xml
 */
require_once 'Zend/Config/Xml.php';

/**
 * @category   Zend
 * @package    Zend_Config
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Config_XmlTest extends PHPUnit_Framework_TestCase
{
    protected $_xmlFileConfig;
    protected $_xmlFileAllSectionsConfig;
    protected $_xmlFileCircularConfig;

    public function setUp()
    {
        $this->_xmlFileConfig = dirname(__FILE__) . '/_files/config.xml';
        $this->_xmlFileAllSectionsConfig = dirname(__FILE__) . '/_files/allsections.xml';
        $this->_xmlFileCircularConfig = dirname(__FILE__) . '/_files/circular.xml';
    }

    public function testLoadSingleSection()
    {
        $config = new Zend_Config_Xml($this->_xmlFileConfig, 'all');

        $this->assertEquals('all', $config->hostname);
        $this->assertEquals('live', $config->db->name);
        $this->assertEquals('multi', $config->one->two->three);
        $this->assertNull(@$config->nonexistent); // property doesn't exist
    }

    public function testSectionInclude()
    {
        $config = new Zend_Config_Xml($this->_xmlFileConfig, 'staging');
        $this->assertEquals('false', $config->debug); // only in staging
        $this->assertEquals('thisname', $config->name); // only in all
        $this->assertEquals('username', $config->db->user); // only in all (nested version)
        $this->assertEquals('staging', $config->hostname); // inherited and overridden
        $this->assertEquals('dbstaging', $config->db->name); // inherited and overridden
    }

    public function testMultiDepthExtends()
    {
        $config = new Zend_Config_Xml($this->_xmlFileConfig, 'other_staging');

        $this->assertEquals('otherStaging', $config->only_in); // only in other_staging
        $this->assertEquals('false', $config->debug); // 1 level down: only in staging
        $this->assertEquals('thisname', $config->name); // 2 levels down: only in all
        $this->assertEquals('username', $config->db->user); // 2 levels down: only in all (nested version)
        $this->assertEquals('staging', $config->hostname); // inherited from two to one and overridden
        $this->assertEquals('dbstaging', $config->db->name); // inherited from two to one and overridden
        $this->assertEquals('anotherpwd', $config->db->pass); // inherited from two to other_staging and overridden
    }

    public function testErrorNoInitialSection()
    {
        try {
            $config = @new Zend_Config_Xml($this->_xmlFileConfig, 'notthere');
            $this->fail('An expected Zend_Config_Exception has not been raised');
        } catch (Zend_Config_Exception $expected) {
            $this->assertContains('cannot be found in', $expected->getMessage());
        }
        
        try {
            $config = @new Zend_Config_Xml($this->_xmlFileConfig, array('notthere', 'all'));
            $this->fail('An expected Zend_Config_Exception has not been raised');
        } catch (Zend_Config_Exception $expected) {
            $this->assertContains('cannot be found in', $expected->getMessage());
        }        
    }

    public function testErrorNoExtendsSection()
    {
        try {
            $config = new Zend_Config_Xml($this->_xmlFileConfig, 'extendserror');
            $this->fail('An expected Zend_Config_Exception has not been raised');
        } catch (Zend_Config_Exception $expected) {
            $this->assertContains('cannot be found', $expected->getMessage());
        }
    }

    public function testZF413_MultiSections()
    {
        $config = new Zend_Config_Xml($this->_xmlFileAllSectionsConfig, array('staging','other_staging'));

        $this->assertEquals('otherStaging', $config->only_in);
        $this->assertEquals('staging', $config->hostname);
    }

    public function testZF413_AllSections()
    {
        $config = new Zend_Config_Xml($this->_xmlFileAllSectionsConfig, null);
        $this->assertEquals('otherStaging', $config->other_staging->only_in);
        $this->assertEquals('staging', $config->staging->hostname);
    }

    public function testZF414()
    {
        $config = new Zend_Config_Xml($this->_xmlFileAllSectionsConfig, null);
        $this->assertEquals(null, $config->getSectionName());
        $this->assertEquals(true, $config->areAllSectionsLoaded());

        $config = new Zend_Config_Xml($this->_xmlFileAllSectionsConfig, 'all');
        $this->assertEquals('all', $config->getSectionName());
        $this->assertEquals(false, $config->areAllSectionsLoaded());

        $config = new Zend_Config_Xml($this->_xmlFileAllSectionsConfig, array('staging','other_staging'));
        $this->assertEquals(array('staging','other_staging'), $config->getSectionName());
        $this->assertEquals(false, $config->areAllSectionsLoaded());
    }

    public function testZF415()
    {
        try {
            $config = new Zend_Config_Xml($this->_xmlFileCircularConfig, null);
            $this->fail('An expected Zend_Config_Exception has not been raised');
        } catch (Zend_Config_Exception $expected) {
            $this->assertContains('circular inheritance', $expected->getMessage());
        }
    }
    
    public function testErrorNoFile()
    {
        try {
            $config = new Zend_Config_Xml('',null);
            $this->fail('An expected Zend_Config_Exception has not been raised');
        } catch (Zend_Config_Exception $expected) {
            $this->assertContains('Filename is not set', $expected->getMessage());
        }
    }
}
