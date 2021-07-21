<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_User_ActiveDirectory
 */
class Tinebase_User_ActiveDirectoryTest extends TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('ACTIVEDIRECTORY backend not enabled');
        }

        parent::setUp();
    }

    /**
     * testConvertADTimestamp
     *
     * @see 0011074: Active Directory as User Backend
     */
    public function testConvertADTimestamp()
    {
        $timestamps = array(
            '130764553441237094'  => '2015-05-18 20:42:24',
            '130791798699200155'  => '2015-06-19 09:31:09',
            '9223372036854775807' => '30828-09-14 02:48:05',
        );

        foreach ($timestamps as $timestamp => $expected) {
            $this->assertEquals($expected, Tinebase_User_ActiveDirectory::convertADTimestamp($timestamp)->toString());
        }

        // sometimes the ad timestamp is a float. i could not reproduce this case
        // let's create a value like this and pass it directly to Tinebase_DateTime
        $date = new Tinebase_DateTime('1391776840.7434058000');
        $this->assertEquals('2014-02-07 12:40:40', $date->toString());
    }
}
