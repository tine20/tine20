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
 * @package    Zend_Currency
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Zend_Currency
 */
require_once 'Zend/Locale.php';
require_once 'Zend/Currency.php';


/**
 * PHPUnit test case
 */
require_once 'PHPUnit/Framework.php';


/**
 * @package    Zend_Currency
 * @subpackage UnitTests
 */
class Zend_CurrencyTest extends PHPUnit_Framework_TestCase
{

    /**
     * tests the creation of Zend_Currency
     */
    public function testSingleCreation()
    {
        $locale = new Zend_Locale('de_AT');

        $currency = new Zend_Currency();
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('de_AT');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '€ 1.000');

        $currency = new Zend_Currency($locale);
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '€ 1.000');

        try {
            $currency = new Zend_Currency('de_XX');
            $this->fail("locale should always include region and therefor not been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        try {
            $currency = new Zend_Currency('xx_XX');
            $this->fail("unknown locale should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        $currency = new Zend_Currency('Latn');
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('Arab');
        $this->assertTrue($currency instanceof Zend_Currency);

        try {
            $currency = new Zend_Currency('Unkn');
            $this->fail("unknown script should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        $currency = new Zend_Currency('EUR');
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('USD');
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('AWG');
        $this->assertTrue($currency instanceof Zend_Currency);

        try {
            $currency = new Zend_Currency('XYZ');
            $this->fail("unknown shortname should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }
    }


    /**
     * tests the creation of Zend_Currency
     */
    public function testDualCreation()
    {
        $locale = new Zend_Locale('de_AT');

        $currency = new Zend_Currency('USD', 'de_AT');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('USD', $locale);
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('de_AT', 'USD');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency($locale, 'USD');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('EUR', 'de_AT');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '€ 1.000');

        try {
            $currency = new Zend_Currency('EUR', 'xx_YY');
            $this->fail("unknown locale should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        $currency = new Zend_Currency('USD', 'Arab');
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('USD', 'Latn');
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('Arab', 'USD');
        $this->assertTrue($currency instanceof Zend_Currency);

        $currency = new Zend_Currency('Latn', 'USD');
        $this->assertTrue($currency instanceof Zend_Currency);

        try {
            $currency = new Zend_Currency('EUR', 'Xyyy');
            $this->fail("unknown script should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }
    }


    /**
     * tests the creation of Zend_Currency
     */
    public function testTripleCreation()
    {
        $locale = new Zend_Locale('de_AT');

        $currency = new Zend_Currency('USD', 'Arab', 'de_AT');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ ١.٠٠٠');

        $currency = new Zend_Currency('USD', 'Latn', $locale);
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        try {
            $currency = new Zend_Currency('XXX', 'Latin', $locale);
            $this->fail("unknown shortname should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        try {
            $currency = new Zend_Currency('USD', 'Xyzz', $locale);
            $this->fail("unknown script should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        try {
            $currency = new Zend_Currency('USD', 'Latin', 'xx_YY');
            $this->fail("unknown locale should not have been recognised");
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        $currency = new Zend_Currency('Arab', 'USD', 'de_AT');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ ١.٠٠٠');

        $currency = new Zend_Currency('Latn', 'USD', $locale);
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('Arab', 'de_AT', 'EUR');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '€ ١.٠٠٠');

        $currency = new Zend_Currency('Latn', $locale, 'USD');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('EUR', 'de_AT', 'Arab');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '€ ١.٠٠٠');

        $currency = new Zend_Currency('USD', $locale, 'Latn');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('de_AT', 'USD', 'Arab');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ ١.٠٠٠');

        $currency = new Zend_Currency($locale, 'USD', 'Latn');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ 1.000');

        $currency = new Zend_Currency('de_AT', 'Arab', 'USD');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '$ ١.٠٠٠');

        $currency = new Zend_Currency($locale, 'Latn', 'EUR');
        $this->assertTrue($currency instanceof Zend_Currency);
        $this->assertSame($currency->toCurrency(1000), '€ 1.000');
    }


    /**
     * tests failed creation of Zend_Currency
     */
    public function testFailedCreation()
    {
        $locale = new Zend_Locale('de_AT');

        try {
            $currency = new Zend_Currency('de_AT', 'en_US');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        try {
            $currency = new Zend_Currency('USD', 'EUR');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        try {
            $currency = new Zend_Currency('Arab', 'Latn');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        try {
            $currency = new Zend_Currency('EUR');
            $currency->toCurrency('value');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }

        $currency = new Zend_Currency('EUR', 'de_AT');
        $currency->setFormat('SIGN');
        $this->assertSame($currency->toCurrency(1000), 'SIGN 1.000');

        try {
            $currency = new Zend_Currency('EUR');
            $currency->setFormat(null, null, 'xy_ZY');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }
    }


    /*
     * testing toCurrency
     */
    public function testToCurrency()
    {
        $USD = new Zend_Currency('USD','en_US');
        $EGP = new Zend_Currency('EGP','ar_EG');

        $this->assertSame($USD->toCurrency(53292.18), '$ 53,292.18');
        $this->assertSame($USD->toCurrency(53292.18, 'Arab'), '$ ٥٣,٢٩٢.١٨');
        $this->assertSame($USD->toCurrency(53292.18, 'Arab', 'de_AT'), '$ ٥٣.٢٩٢,١٨');
        $this->assertSame($USD->toCurrency(53292.18, null, 'de_AT'), '$ 53.292,18');

        $this->assertSame($EGP->toCurrency(53292.18), 'ج.م.‏ 53٬292٫18');
        $this->assertSame($EGP->toCurrency(53292.18, 'Arab'), 'ج.م.‏ ٥٣٬٢٩٢٫١٨');
        $this->assertSame($EGP->toCurrency(53292.18, 'Arab', 'de_AT'), 'ج.م.‏ ٥٣.٢٩٢,١٨');
        $this->assertSame($EGP->toCurrency(53292.18, null, 'de_AT'), 'ج.م.‏ 53.292,18');

        $USD = new Zend_Currency('en_US');
        $this->assertSame($USD->toCurrency(53292.18), '$ 53,292.18');
    }


    /**
     * testing setFormat
     *
     */
    public function testSetFormat()
    {
        $USD = new Zend_Currency('USD','en_US');

        $USD->setFormat(null, 'Arab');
        $this->assertSame($USD->toCurrency(53292.18), '$ ٥٣,٢٩٢.١٨');

        $USD->setFormat(null, 'Arab', 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '$ ٥٣.٢٩٢,١٨');

        $USD->setFormat(null, 'Latn', 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '$ 53.292,18');

        // allignment of currency signs
        $USD->setFormat(Zend_Currency::RIGHT, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '53.292,18 $');

        $USD->setFormat(Zend_Currency::LEFT, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '$ 53.292,18');

        $USD->setFormat(Zend_Currency::STANDARD, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '$ 53.292,18');

        // enable/disable currency symbols & currency names
        $USD->setFormat(Zend_Currency::NO_SYMBOL, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '53.292,18');

        $USD->setFormat(Zend_Currency::USE_SHORTNAME, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), 'USD 53.292,18');

        $USD->setFormat(Zend_Currency::USE_NAME, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), 'US Dollar 53.292,18');

        $USD->setFormat(Zend_Currency::USE_SYMBOL, null, 'de_AT');
        $this->assertSame($USD->toCurrency(53292.18), '$ 53.292,18');
    }


    /**
     * test getSign
     */
    public function testGetSign()
    {
        $locale = new Zend_Locale('ar_EG');

        $this->assertSame(Zend_Currency::getSymbol('EGP','ar_EG'), 'ج.م.‏');
        $this->assertSame(Zend_Currency::getSymbol('ar_EG'), 'ج.م.‏');
        $this->assertSame(Zend_Currency::getSymbol('ar_EG'), 'ج.م.‏');
        try {
            $this->assertSame(is_string(Zend_Currency::getSymbol('EGP')), true);
        } catch (Zend_Currency_Exception $e) {
            // Systems without locale are expected to be ok from the testbed
            $this->assertSame($e->getMessage(), "Locale 'root' is no valid locale");
        }

        try {
            Zend_Currency::getSymbol('EGP', 'de_XX');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }
    }


    /**
     * test getName
     */
    public function testGetName()
    {
        $locale = new Zend_Locale('ar_EG');

        $this->assertSame(Zend_Currency::getName('EGP','ar_EG'), 'جنيه مصرى');
        $this->assertSame(Zend_Currency::getName('EGP',$locale), 'جنيه مصرى');
        $this->assertSame(Zend_Currency::getName('ar_EG'), 'جنيه مصرى');
        try {
            $this->assertSame(is_string(Zend_Currency::getName('EGP')), true);
        } catch (Zend_Currency_Exception $e) {
            // Systems without locale are expected to be ok from the testbed
            $this->assertSame($e->getMessage(), "Locale 'root' is no valid locale");
        }

        try {
            Zend_Currency::getName('EGP', 'xy_XY');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }
    }


    /**
     * test getShortName
     */
    public function testGetShortName()
    {
        $locale = new Zend_Locale('de_AT');

        $this->assertSame(Zend_Currency::getShortName('EUR','de_AT'), 'Euro');
        $this->assertSame(Zend_Currency::getShortName('EUR',$locale), 'Euro');
        $this->assertSame(Zend_Currency::getShortName('de_AT'), 'Euro');
        try {
            $this->assertSame(is_string(Zend_Currency::getShortName('EUR')), true);
        } catch (Zend_Currency_Exception $e) {
            // Systems without locale are expected to be ok from the testbed
            $this->assertSame($e->getMessage(), "Locale 'root' is no valid locale");
        }

        try {
            Zend_Currency::getShortName('EUR', 'xy_ZT');
            $this->fail();
        } catch (Zend_Currency_Exception $e) {
            // success
        }
    }


    /**
     * testing getRegionList
     */
    public function testGetRegionList()
    {
        $this->assertTrue( is_array(Zend_Currency::getRegionList('USD')) );
    }


    /**
     * testing getCurrencyList
     */
    public function testGetCurrencyList()
    {
        $this->assertTrue( is_array(Zend_Currency::getCurrencyList('EG')) );
    }


    /**
     * testing toString
     *
     */
    public function testToString()
    {
        $USD = new Zend_Currency('USD','en_US');
        $this->assertSame($USD->toString(), 'US Dollar');
        $this->assertSame($USD->__toString(), 'US Dollar');
    }
}
