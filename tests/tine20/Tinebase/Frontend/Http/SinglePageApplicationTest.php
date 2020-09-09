<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Tinebase_Frontend_Http
 */
class Tinebase_Frontend_Http_SinglePageApplicationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @group needsbuild
     */
    public function testGetAssetHash()
    {
        $this->assertTrue(is_string(Tinebase_Frontend_Http_SinglePageApplication::getAssetHash()));
    }
}