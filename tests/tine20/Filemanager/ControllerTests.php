<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Filemanager_ControllerTests
 * 
 * @package     Filemanager
 */
class Filemanager_ControllerTests extends TestCase
{

    protected function tearDown()
    {
        parent::tearDown();

        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION, new Tinebase_Config_Struct(array(
        )));
    }

    /**
     * @throws Admin_Exception
     * @throws Exception
     */
    public function testCreatePersonalContainer()
    {
        // create user
        $pw = Tinebase_Record_Abstract::generateUID();
        $user = Admin_Controller_User::getInstance()->create($this->getTestUser(), $pw, $pw);

        // check if personal folder exists
        $personalFolderPath = $this->_getPersonalPath($user);
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        $personalFolderName = sprintf($translation->_("%s's personal files"), $user->accountFullName);

        $node = Tinebase_FileSystem::getInstance()->stat($personalFolderPath . '/' . $personalFolderName);
        $this->assertEquals($personalFolderName, $node->name);

        return $user;
    }

    /**
     * @throws Admin_Exception
     * @throws Exception
     */
    public function testDeletePersonalContainer()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION, new Tinebase_Config_Struct(array(
            '_deletePersonalContainers' => true,
        )));

        $user = $this->testCreatePersonalContainer();
        Admin_Controller_User::getInstance()->delete(array($user->getId()));

        // check if personal folder exists
        $personalFolderPath = $this->_getPersonalPath($user);
        self::setExpectedException('Tinebase_Exception_NotFound', 'child:');
        Tinebase_FileSystem::getInstance()->stat($personalFolderPath);
    }
}
