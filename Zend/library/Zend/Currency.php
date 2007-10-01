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
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id$
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * include needed classes
 */
require_once 'Zend/Locale.php';
require_once 'Zend/Locale/Data.php';
require_once 'Zend/Locale/Format.php';
require_once 'Zend/Currency/Exception.php';


/**
 * @category   Zend
 * @package    Zend_Currency
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Currency
{
    // constants for defining what currency symbol should be displayed
    const NO_SYMBOL     = 1;
    const USE_SYMBOL    = 2;
    const USE_SHORTNAME = 3;
    const USE_NAME      = 4;

    // constants for defining the position of the currencysign
    const STANDARD = 8;
    const RIGHT    = 16;
    const LEFT     = 32;

    /**
     * the locale name of the region that uses the currency
     *
     * @var string
     */
    private $_locale = null;

    /**
     * the short name of the currency
     *
     * @var string
     */
    private $_shortName = null;

    /**
     * the full name of the currency
     *
     * @var string
     */
    private $_fullName = null;

    /**
     * the symbol of the currency
     *
     * @var string
     */
    private $_symbol = null;

    /**
     * the position of the symbol
     *
     * @var const
     */
    private $_position = null;

    /**
     * the script name which used to format the outputed numbers
     *
     * @var string
     */
    private $_script = null;

    /**
     * the locale for formating the output
     *
     * @var string
     */
    private $_formatLocale = null;

    /**
     * which sign to use for currency display
     *
     * @var const
     */
    private $_usedSign = 1;

    /**
     * Creates a currency instance. Every supressed parameter is used from the actual or the given locale.
     *
     * @param  string              $currency  OPTIONAL currency short name
     * @param  string              $script    OPTIONAL script name
     * @param  string|Zend_Locale  $locale    OPTIONAL locale name
     * @return Zend_Currency
     * @throws Zend_Currency_Exception
     */
    public function __construct($currency = null, $script = null, $locale = null)
    {
         // supporting flexible parameters
        $params = array(1 => $currency, 2 => $locale, 3 => $script);
        foreach ($params as $num => $param){
            // get the locale
            if ($param instanceof Zend_Locale) {
                $param = $param->toString();
            }
            if (($locale = Zend_Locale::isLocale($param)) && (strlen($param) > 4)) {
                if ($locale != $param) {
                    throw new Zend_Currency_Exception("Unknown locale or locale without a region passed");
                }
                if (!empty($this->_locale)) {
                    throw new Zend_Currency_Exception("Multiple locales passed. Please provide only one locale");
                }
                if ($param instanceof Zend_Locale) {
                    $param = $param->toString();
                }
                $this->_locale = $param;
            // get the currency short name
            } else if (is_string($param) && strlen($param) == 3) {

                if(!empty($this->_shortName)) {
                    throw new Zend_Currency_Exception("Multiple currencies passed. Please provide only one currency");
                }
                $this->_shortName = $param;
            // get the script name
            } else if (is_string($param) && (strlen($param) == 4)) {

                if (!empty($this->_script)) {
                    throw new Zend_Currency_Exception("Multiple number script names passed. Please provide only one script");
                }
                try {
                    Zend_Locale_Format::convertNumerals('0', $param);
                } catch (Exception $e) {
                    throw new Zend_Currency_Exception($e->getMessage());
                }
                $this->_script = $param;
            // unknown data passed in this param
            } else if ($param !== null){
                throw new Zend_Currency_Exception("Unknown locale '$param' passed with param #$num, locale must include the region");
            }
        }

        // if no locale is passed, use standard locale
        if (empty($this->_locale)) {
            $locale = new Zend_Locale();
            $this->_locale = $locale->toString();
        }

        // get/check the shortname
        $data = Zend_Locale_Data::getContent(null, 'currencyforregionlist');
        if (!empty($this->_shortName)) {
            if (!in_array($this->_shortName, $data)) {
                throw new Zend_Currency_Exception("Unknown currency '$this->_shortName' passed");
            }
        } else {
            if (array_key_exists(substr($this->_locale, strpos($this->_locale, '_') + 1), $data)) {
                $this->_shortName = $data[substr($this->_locale, strpos($this->_locale, '_') + 1)];
            }
        }

        // get the fullname
        $names = Zend_Locale_Data::getContent($this->_locale, 'currencynames', substr($this->_locale, strpos($this->_locale, '_') + 1) );
        $this->_fullName = isset($names[$this->_shortName]) ? $names[$this->_shortName] : '';

        // get the symbol
        $symbols = Zend_Locale_Data::getContent($this->_locale, 'currencysymbols');
        $this->_symbol = isset($symbols[$this->_shortName]) ? $symbols[$this->_shortName] : '';

        // get the format
        $this->_updateFormat();
        $this->_usedSign = self::NO_SYMBOL;
        if (!empty($this->_symbol)) {
            $this->_usedSign = self::USE_SYMBOL;
        } else if (!empty($this->_shortName)) {
            $this->_usedSign = self::USE_SHORTNAME;
        }
        return $this;
    }


    /**
     * Gets the information required for formating the currency from Zend_Locale
     *
     * @return Zend_Currency
     * @throws Zend_Currency_Exception
     */
    protected function _updateFormat()
    {
        if (empty($this->_formatLocale)) {
            $locale = $this->_locale;
        } else {
            $locale = $this->_formatLocale;
        }

        //getting the format information of the currency
        $format = Zend_Locale_Data::getContent($locale, 'currencyformat');
        $format = $format['default'];

        iconv_set_encoding('internal_encoding', 'UTF-8');
        if (iconv_strpos($format, ';')) {
            $format = iconv_substr($format, 0, iconv_strpos($format, ';'));
        }

        //knowing the sign positioning information
        if (iconv_strpos($format, '¤') == 0) {
            $this->_position = self::LEFT;
        } else if (iconv_strpos($format, '¤') == iconv_strlen($format)-1) {
            $this->_position = self::RIGHT;
        }

        return $this;
    }


    /**
     * Returns a localized currency string
     *
     * @param  int|float           $value   Currency value
     * @param  string              $script  OPTIONAL Number script to use for output
     * @param  string|Zend_Locale  $locale  OPTIONAL Locale for output formatting
     * @return string
     */
    public function toCurrency($value, $script = NULL, $locale = NULL)
    {
        //validate the passed number
        if (!isset($value) || !is_numeric($value)) {
            throw new Zend_Currency_Exception("Value '$value' must be an number");
        }

        //format the number
        if (empty($locale)) {
            if (empty($this->_formatLocale)) {
                $locale = $this->_locale;
            } else {
                $locale = $this->_formatLocale;
            }
        }
        $value = Zend_Locale_Format::toNumber($value, array('locale' => $locale));

        //localize the number digits
        if (empty($script)) {
            $script = $this->_script;
        }
        if (!empty ($script)) {
            $value = Zend_Locale_Format::convertNumerals($value, 'Latn', $script);
        }
        //get the sign to be placed next to the number
        if (!is_numeric($this->_usedSign)) {
            $sign = " " . $this->_usedSign . " ";
        } else {
            switch($this->_usedSign) {
                case self::USE_SYMBOL:
                    $sign = " " . $this->_symbol . " ";
                    break;
                case self::USE_SHORTNAME:
                    $sign = " " . $this->_shortName . " ";
                    break;
                case self::USE_NAME:
                    $sign = " " . $this->_fullName . " ";
                    break;
                default:
                    $sign = "";
                    break;
            }
        }

        //place the sign next to the number
        if ($this->_position == self::RIGHT) {
            $value = $value . $sign;
        } else if ($this->_position == self::LEFT) {
            $value = $sign . $value;
        }

        return trim($value);
    }


    /**
     * Sets the formating options of the localized currency string
     * If no parameter is passed, the standard setting of the
     * actual set locale will be used
     *
     * @param  const|string        $rules   OPTIONAL formating rules for currency
     *                  - USE_SYMBOL|NOSYMBOL : display currency symbol
     *                  - USE_NAME|NONAME     : display currency name
     *                  - STANDARD|RIGHT|LEFT : where to display currency symbol/name
     *                  - string: gives the currency string/name/sign to set
     * @param  string              $script  OPTIONAL Number script to use for output
     * @param  string|Zend_Locale  $locale  OPTIONAL Locale for output formatting
     * @return Zend_Currency
     */
    public function setFormat($rules = null, $script = null, $locale = null)
    {
        if (!is_numeric($rules) and ($rules !== null)) {
            $this->_usedSign = $rules;
        } else {
            if (($rules / self::LEFT) >= 1) {
                $this->_position = self::LEFT;
                $rules -= self::LEFT;
            }
            if (($rules / self::RIGHT) >= 1) {
                $this->_position = self::RIGHT;
                $rules -= self::RIGHT;
            }
            if (($rules / self::STANDARD) >= 1) {
                $this->_updateFormat();
                $rules -= self::STANDARD;
            }
            if (!empty($rules)) {
                $this->_usedSign = $rules;
            }
        }

        //set the new number script
        if (!empty($script)) {
            try {
                Zend_Locale_Format::convertNumerals(0,$script);
                $this->_script = $script;
            } catch (Zend_Locale_Exception $e) {
                throw new Zend_Currency_Exception($e->getMessage());
            }
        }

        //set the locale for the number formating process
        if (!empty($locale)) {
            if ($locale instanceof Zend_Locale) {
                $locale = $locale->toString();
            }
            if ($locale = Zend_Locale::isLocale($locale) and (strlen($locale) > 4)) {
                $this->_formatLocale = $locale;
            } else {
                throw new Zend_Currency_Exception("Locale '$locale' is no valid locale");
            }
        }
        return $this;
    }


    /**
     * Returns the actual or details of other currency symbols,
     * when no symbol is avaiable it returns the currency shortname (f.e. FIM for Finnian Mark)
     *
     * @param  string              $currency   OPTIONAL Currency name
     * @param  string|Zend_Locale  $locale     OPTIONAL Locale to display informations
     * @return string
     */
    public static function getSymbol($currency = null, $locale = null)
    {
        //manage the params
        if (empty($locale) && !empty($currency) && (Zend_Locale::isLocale($currency))) {
            $locale = $currency;
            $currency = null;
        } else if (empty($locale)) {
            $locale = new Zend_Locale();
        }

        //validate the locale and get the country short name
        $country = null;
        if ($locale instanceof Zend_Locale) {
            $locale = $locale->toString();
        }
        if ($locale = Zend_Locale::isLocale($locale) and (strlen($locale) > 4)) {
            $country = substr($locale, strpos($locale, '_')+1 );
        } else {
            throw new Zend_Currency_Exception("Locale '$locale' is no valid locale");
        }

        //get the available currencies for this country
        $data = Zend_Locale_Data::getContent($locale, 'currencyforregion', $country);
        if (!empty($currency)) {
            if (isset($data[$currency])) {
                $shortName = $currency;
            } else {
                return key($data);
            }
        } else {
            $shortName = key($data);
        }

        //get the symbol
        $symbols = Zend_Locale_Data::getContent($locale, 'currencysymbols');
        return isset($symbols[$shortName]) ? $symbols[$shortName] : $shortName;
    }


    /**
     * Returns the actual or details of other currency shortnames
     *
     * @param  string              $currency   OPTIONAL Currency's short name
     * @param  string|Zend_Locale  $locale     OPTIONAL the locale
     * @return string
     */
    public static function getShortName($currency = null, $locale = null)
    {
        //manage the params
        if (empty($locale) && !empty($currency) && (Zend_Locale::isLocale($currency))) {
            $locale = $currency;
            $currency = null;
        } else if (empty($locale)) {
            $locale = new Zend_Locale();
        }

        //validate the locale and get the country short name
        $country = null;
        if ($locale instanceof Zend_Locale) {
            $locale = $locale->toString();
        }
        if ($locale = Zend_Locale::isLocale($locale) and (strlen($locale) > 4)) {
            $country = substr($locale, strpos($locale, '_') + 1 );
        } else {
            throw new Zend_Currency_Exception("Locale '$locale' is no valid locale");
        }

        //get the available currencies for this country
        $data = Zend_Locale_Data::getContent($locale,'currencyforregion',$country);
        if (!empty($currency)) {
            if (isset($data[$currency])) {
                $shortName = $currency;
            } else {
                return key($data);
            }
        } else {
            $shortName = key($data);
        }

        //get the name
        $names = Zend_Locale_Data::getContent($locale, 'currencynames', $country);

        return isset($names[$shortName]) ? $names[$shortName] : $shortName;
    }


    /**
     * Returns the actual or details of other currency names
     *
     * @param  string              $currency   OPTIONAL Currency's short name
     * @param  string|Zend_Locale  $locale     OPTIONAL the locale
     * @return string
     */
    public static function getName($currency = null, $locale = null)
    {
        //manage the params
        if (empty($locale) && !empty($currency) && (Zend_Locale::isLocale($currency))) {
            $locale = $currency;
            $currency = null;
        } else if (empty($locale)) {
            $locale = new Zend_Locale();
        }

        //validate the locale and get the country short name
        $country = null;
        if ($locale instanceof Zend_Locale) {
            $locale = $locale->toString();
        }
        if ($locale = Zend_Locale::isLocale($locale) and (strlen($locale) > 4)) {
            $country = substr($locale, strpos($locale, '_') + 1 );
        } else {
            throw new Zend_Currency_Exception("Locale '$locale' is no valid locale");
        }

        //get the available currencies for this country
        $data = Zend_Locale_Data::getContent($locale,'currencyforregion',$country);
        if (!empty($currency)) {
            if (isset($data[$currency])) {
                $shortName = $currency;
            } else {
                return key($data);
            }
        } else {
            $shortName = key($data);
        }

        //get the name
        $names = Zend_Locale_Data::getContent($locale, 'currencynames', $country);

        return isset($names[$shortName]) ? $names[$shortName] : $shortName;
    }


    /**
     * Returns a list of regions where this currency is or was known
     *
     * @param  string  $currency  Currency's short name
     * @return array              List of regions
     */
    public static function getRegionList($currency)
    {
        $data = Zend_Locale_Data::getContent('', 'currencyforregionlist');
        $regionList = array();

        foreach($data as $region => $currencyShortName) {
            if ($currencyShortName == $currency) {
                $regionList[] = $region;
            }
        }

        return $regionList;
    }


    /**
     * Returns a list of currencies which are used in this region
     * a region name should be 2 charachters only (f.e. EG, DE, US)
     *
     * @param  string  $region  Currency Type
     * @return array            List of currencys
     */
    public static function getCurrencyList($region)
    {
        return Zend_Locale_Data::getContent('', 'currencyforregion', $region);
    }


    /**
     * Returns the actual currency name
     *
     * @return string
     */
    public function toString()
    {
        if (!empty($this->_fullName)) {
            return $this->_fullName;
        } else {
            return $this->_shortName;
        }
    }


    /**
     * Returns the currency name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * sets a cache for Zend_Currency
     * 
     * @param Zend_Cache_Core $cache  Cache to set
     */
    public static function setCache(Zend_Cache_Core $cache)
    {
        Zend_Locale_Data::setCache($cache);
    }
}
