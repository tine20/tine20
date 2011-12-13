<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Filemanager_Frontend_WebDavTest::main');
}

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Tinebase
 */
class Filemanager_Frontend_WebDavTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Tree
     *
     * @var Tinebase_WebDav_Tree
     */
    protected $_webdavTree;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 webdav tree tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_webdavTree = new Tinebase_WebDav_Tree('/');
        
        $this->objects['nodes'] = array();
        
        try {
            $container = Tinebase_Container::getInstance()->getContainerByName('Filemanager', 'unittestdirectory', Tinebase_Model_Container::TYPE_SHARED);
            
            // container exists already => remove him
            Tinebase_Container::getInstance()->delete($container);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // continue
        }
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['nodes'] as $node) {
            $this->_rmDir($node);
        } 
    }
    
    protected function _rmDir($_node)
    {
        foreach ($_node->getChildren() as $child) {
            if ($child instanceof Filemanager_Frontend_WebDav_File) {
                $child->delete();
            } else {
                $this->_rmDir($child);
            }
        }
        $_node->delete();
    }
    
    public function testgetNodeForPath()
    {
        $node = $this->_webdavTree->getNodeForPath(null);
        
        $this->assertEquals('Tinebase_WebDav_Root', get_class($node), 'wrong type');
        
        $children = $node->getChildren();
        
        $this->assertEquals(Tinebase_WebDav_Root::ROOT_NODE, $children[0]->getName());
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav()
    {
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE);
        
        $this->assertEquals('Tinebase_WebDav_Root', get_class($node), 'wrong type');
        $this->assertEquals(Tinebase_WebDav_Root::ROOT_NODE, $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($children[0]), 'wrong type');
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav_filemanager()
    {
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager');
        
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($node), 'wrong type');
        $this->assertEquals('filemanager', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertEquals(2, count($children));
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($children[0]), 'wrong type');
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav_filemanager_personal()
    {
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/personal');
        
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($node), 'wrong type');
        $this->assertEquals('personal', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($children[0]), 'wrong type');
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    public function testgetNodeForPath_dav_filemanager_personal_currentuser()
    {
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/personal/' . Tinebase_Core::getUser()->accountLoginName);
        
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($node), 'wrong type');
        $this->assertEquals(Tinebase_Core::getUser()->accountLoginName, $node->getName());
        
        $children = $node->getChildren();
        #var_dump($children);
        $this->assertGreaterThanOrEqual(1, count($children));
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($children[0]), 'wrong type');
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    /**
     * @return Filemanager_Frontend_WebDav_Directory
     */
    public function testgetNodeForPath_dav_filemanager_personal_currentuser_unittestdirectory()
    {
        $this->testCreatePersonalDirectory();
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/personal/' . Tinebase_Core::getUser()->accountLoginName .'/unittestdirectory');
        
        $this->assertEquals('Filemanager_Frontend_WebDav_Directory', get_class($node), 'wrong type');
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $node->getChildren();
        
        return $node;
    }
    
    /**
     * 
     * @return Filemanager_Frontend_WebDav_Directory
     */
    public function testCreatePersonalDirectory()
    {
        try {
            // remove file left over from broken test
            $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/personal/' . Tinebase_Core::getUser()->accountLoginName . '/unittestdirectory');
            $node->delete();
        } catch (Sabre_DAV_Exception_FileNotFound $sdavefnf) {
            // do nothing
        }
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/personal/' . Tinebase_Core::getUser()->accountLoginName);
        
        $node->createDirectory('unittestdirectory');
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/personal/' . Tinebase_Core::getUser()->accountLoginName . '/unittestdirectory');
        $this->objects['nodes'][] = $node;
        
        $this->assertEquals('Filemanager_Frontend_WebDav_Directory', get_class($node), 'wrong type');
        
        return $node;
    }
    
    public function testgetNodeForPath_dav_filemanager_shared()
    {
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared');
        
        $this->assertEquals('Filemanager_Frontend_WebDav', get_class($node), 'wrong type');
        $this->assertEquals('shared', $node->getName());
        
        $children = $node->getChildren();
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $node->delete();
    }
    
    /**
     * @return Filemanager_Frontend_WebDav_Directory
     */
    public function testgetNodeForPath_dav_filemanager_shared_unittestdirectory()
    {
        $this->testCreateSharedDirectory();
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/unittestdirectory');
        
        $this->assertEquals('Filemanager_Frontend_WebDav_Directory', get_class($node), 'wrong type');
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $node->getChildren();
        
        return $node;
    }
    
    public function testgetNodeForPath_dav_filemanager_shared_unittestdirectory_file()
    {
        $this->testCreateFile();
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/unittestdirectory/tine_logo.png');
        
        $this->assertEquals('Filemanager_Frontend_WebDav_File', get_class($node), 'wrong type');
        $this->assertEquals('tine_logo.png', $node->getName());
    }
    
    /**
     * 
     * @return Filemanager_Frontend_WebDav_Directory
     */
    public function testCreateSharedDirectory()
    {
        try {
            // remove file left over from broken test
            $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/unittestdirectory');
            $node->delete();
        } catch (Sabre_DAV_Exception_FileNotFound $sdavefnf) {
            // do nothing
        }
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared');
        
        $node->createDirectory('unittestdirectory');
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/unittestdirectory');
        $this->objects['nodes'][] = $node;
        
        $this->assertEquals('Filemanager_Frontend_WebDav_Directory', get_class($node), 'wrong type');
        
        return $node;
    }
    
    /**
     * 
     * @return Filemanager_Frontend_WebDav_File
     */
    public function testCreateFile()
    {
        try {
            // remove file left over from broken test
            $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/unittestdirectory/tine_logo.png');
            $node->delete();
        } catch (Sabre_DAV_Exception_FileNotFound $sdavefnf) {
            // do nothing
        }
        
        $parent = $this->testCreateSharedDirectory();
        
        $file = $parent->createFile('tine_logo.png', fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png', 'r'));
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/unittestdirectory/tine_logo.png');
        
        $this->assertEquals('Filemanager_Frontend_WebDav_File', get_class($node), 'wrong type');
        
        return $node;
    }
    
    public function testUpdateFile()
    {
        $node = $this->testCreateFile();
        
        $node->put(fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png', 'r'));
        
        $this->assertEquals('Filemanager_Frontend_WebDav_File', get_class($node), 'wrong type');
    }
    
    public function testgetNodeForPath_invalidApplication()
    {
        $this->setExpectedException('Sabre_DAV_Exception_FileNotFound');
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/invalidApplication');
    }
    
    public function testgetNodeForPath_invalidContainerType()
    {
        $this->setExpectedException('Sabre_DAV_Exception_FileNotFound');
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/invalidContainerType');
    }
    
    public function testgetNodeForPath_invalidFolder()
    {
        $this->setExpectedException('Sabre_DAV_Exception_FileNotFound');
        
        $node = $this->_webdavTree->getNodeForPath(Tinebase_WebDav_Root::ROOT_NODE . '/filemanager/shared/invalidContainer');
    }
    
    /**
     * @return Filemanager_Model_Directory
     */
    #public static function getTestRecord()
    #{
    #    $object  = new Tinebase_Model_Tree_Node(array(
    #        'name'     => 'PHPUnit test node',
    #    ), true); 
    #    
    #    return $object;
    #}
}		
	

if (PHPUnit_MAIN_METHOD == 'Filemanager_Frontend_WebDavTest::main') {
    Filemanager_Frontend_WebDavTest::main();
}
