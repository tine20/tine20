<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Zend_AllTests
 * Tests for Zend rewritten stuff
 * @package     Tinebase
 */
class Zend_AllTests
{
    /**
     * main
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Test for Zend rewritten stuff');
        $suite->addTestSuite('Zend_Translate_Adapter_GettextPoTest');
        $suite->addTestSuite('Zend_Translate_TranslateTest');
        return $suite;
    }
}
