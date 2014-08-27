<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_ModelConfiguration, using the test class from hr
 */
class Tinebase_ModelConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * tests if the modelconfiguration gets created for the traditional models
     */
    public function testModelCreationTraditional()
    {
        $contact = new Addressbook_Model_Contact(array('n_family' => 'Spencer', 'n_given' => 'Bud'));
        $cObj = $contact->getConfiguration();

        // at first this is just null
        $this->assertNull($cObj);
    }
}
