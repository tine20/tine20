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
 * @package    Zend_Translate
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id$
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

require_once 'Zend/Loader.php';


/**
 * @category   Zend
 * @package    Zend_Translate
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Translate {
    /**
     * Adapter names constants
     */
    const AN_ARRAY   = 'array';
    const AN_CSV     = 'csv';
    const AN_GETTEXT = 'gettext';
    const AN_QT      = 'qt';
    const AN_TMX     = 'tmx';
    const AN_XLIFF   = 'xliff';

    /**
     * Adapter
     *
     * @var Zend_Translate_Adapter
     */
    private $_adapter;


    /**
     * Generates the standard translation object
     *
     * @param  string              $adapter  Adapter to use
     * @param  array               $options  Options for this adapter to set
     *                                       Depends on the Adapter
     * @param  string|Zend_Locale  $locale   OPTIONAL locale to use
     * @throws Zend_Translate_Exception
     */
    public function __construct($adapter, $options, $locale = null)
    {
        $this->setAdapter($adapter, $options, $locale);
    }


    /**
     * Sets a new adapter
     *
     * @param  string              $adapter  Adapter to use
     * @param  string|array        $data     Translation data
     * @param  string|Zend_Locale  $locale   OPTIONAL locale to use
     * @param  array               $options  OPTIONAL Options to use
     * @throws Zend_Translate_Exception
     */
    public function setAdapter($adapter, $data, $locale = null, array $options = array())
    {
        switch (strtolower($adapter)) {
            case 'array':
                $adapter = 'Zend_Translate_Adapter_Array';
                break;
            case 'csv':
                $adapter = 'Zend_Translate_Adapter_Csv';
                break;
            case 'gettext':
                $adapter = 'Zend_Translate_Adapter_Gettext';
                break;
            case 'qt':
                $adapter = 'Zend_Translate_Adapter_Qt';
                break;
            case 'tmx':
                $adapter = 'Zend_Translate_Adapter_Tmx';
                break;
            case 'xliff':
                $adapter = 'Zend_Translate_Adapter_Xliff';
                break;
        }

        Zend_Loader::loadClass($adapter);
        $this->_adapter = new $adapter($data, $locale, $options);
        if (!$this->_adapter instanceof Zend_Translate_Adapter) {
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception("Adapter " . $adapter . " does not extend Zend_Translate_Adapter'");
        }
    }


    /**
     * Returns the adapters name and it's options
     *
     * @return Zend_Translate_Adapter
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }


    /**
     * Add translation data.
     *
     * It may be a new language or additional data for existing language
     * If $clear parameter is true, then translation data for specified
     * language is replaced and added otherwise
     *
     * @param  string|array        $data     Translation data
     * @param  string|Zend_Locale  $locale   Locale/Language to add to this adapter
     * @param  array               $options  OPTIONAL Options to use
     */
    public function addTranslation($data, $locale, array $options = array())
    {
        $this->_adapter->addTranslation($data, $locale, $options);
    }


    /**
     * Sets a new locale/language
     *
     * @param  string|Zend_Locale  $locale  Locale/Language to set for translations
     */
    public function setLocale($locale)
    {
        $this->_adapter->setLocale($locale);
    }


    /**
     * Returns the actual set locale/language
     *
     * @return Zend_Locale|null
     */
    public function getLocale()
    {
        return $this->_adapter->getLocale();
    }


    /**
     * Returns all avaiable locales/languages from this adapter
     *
     * @return array
     */
    public function getList()
    {
        return $this->_adapter->getList();
    }


    /**
     * Is the wished language avaiable ?
     *
     * @param  string|Zend_Locale  $locale  Is the locale/language avaiable
     * @return boolean
     */
    public function isAvailable($locale)
    {
        return $this->_adapter->isAvailable($locale);
    }


    /**
     * Translate the given string
     *
     * @param  string              $messageId  Original to translate
     * @param  string|Zend_Locale  $locale     OPTIONAL locale/language to translate to
     * @return string
     */
    public function _($messageId, $locale = null)
    {
        return $this->_adapter->translate($messageId, $locale);
    }


    /**
     * Translate the given string
     *
     * @param  string              $messageId  Original to translate
     * @param  string|Zend_Locale  $locale     OPTIONAL locale/language to translate to
     * @return string
     */
    public function translate($messageId, $locale = null)
    {
        return $this->_adapter->translate($messageId, $locale);
    }


    /**
     * Checks if a given string can be translated
     * returns boolean
     *
     * @param  string              $messageId  Translation string
     * @param  boolean             $original   OPTIONAL Allow translation only for original language
     *                                         when true, a translation for 'en_US' would give false when it can
     *                                         be translated with 'en' only
     * @param  string|Zend_Locale  $locale     OPTIONAL Locale/Language to use, identical with locale identifier,
     *                                         see Zend_Locale for more information
     * @return boolean
     */
    public function isTranslated($messageId, $original = false, $locale = null)
    {
        return $this->_adapter->isTranslated($messageId, $original, $locale);
    }


    /**
     * Returns all actual known message ids as array
     *
     * @return array
     */
    public function getMessageIds()
    {
        return $this->_adapter->getMessageIds();
    }


    /**
     * Returns all known messages with  ids
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_adapter->getMessages();
    }
}
