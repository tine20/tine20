<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_HttpTest extends TestCase
{
    use GetProtectedMethodTrait;

    public function testDownloadFile()
    {
        $jsonTests = new Filemanager_Frontend_JsonTests();
        $file = $jsonTests->testCreateFileNodeWithTempfile();

        $uit = $this->_getUit();
        ob_start();
        $reflectionMethod = $this->getProtectedMethod(Filemanager_Frontend_Http::class, '_downloadFileNodeByPathOrId');
        $reflectionMethod->invokeArgs($uit, [$file['path'], null]);
        $out = ob_get_clean();

        self::assertEquals('test file content', $out);
    }

    public function testDownloadFileWithoutGrant()
    {
        $jsonTests = new Filemanager_Frontend_JsonTests();
        $file = $jsonTests->testCreateFileNodeWithTempfile();

        // remove download grant from folder node
        $testPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager', Tinebase_FileSystem::FOLDER_TYPE_SHARED)
            . '/testcontainer';
        $node = Tinebase_FileSystem::getInstance()->stat($testPath);
        Tinebase_FileSystem::getInstance()->setGrantsForNode($node, Tinebase_Model_Grants::getDefaultGrants());

        $uit = $this->_getUit();
        try {
            $reflectionMethod = $this->getProtectedMethod(Filemanager_Frontend_Http::class, '_downloadFileNodeByPathOrId');
            $reflectionMethod->invokeArgs($uit, [$file['path'], null]);
            self::fail('download should not be allowed');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::assertEquals('download not allowed', $tead->getMessage());
        }
    }
}
