
<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ContainerPersistentCacheTest extends Tinebase_ContainerTest
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        // this shows how to set up the persistent cache for some Tinebase_Container methods
        Tinebase_Cache_PerRequest::getInstance()->setPersistentCacheMethods('Tinebase_Container', array(
            'getContainerByACL',
            '_getOtherAccountIds',
            'getPersonalContainer'
        ));

        parent::setUp();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();

        Tinebase_Cache_PerRequest::getInstance()->setPersistentCacheMethods('Tinebase_Container', array());
        Tinebase_Cache_PerRequest::getInstance()->reset('Tinebase_Container');
    }
}
