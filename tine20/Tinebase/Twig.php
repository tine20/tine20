<?php
/**
 * Tinebase Twig class
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase Twig class
 *
 * @package     Tinebase
 * @subpackage  Twig
 *
 */
class Tinebase_Twig
{
    const TWIG_AUTOESCAPE = 'autoEscape';
    const TWIG_LOADER = 'loader';
    const TWIG_CACHE = 'cache';

    /**
     * @var Twig_Environment
     */
    protected $_twigEnvironment = null;

    /**
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_translate;

    /**
     * locale object
     *
     * @var Zend_Locale
     */
    protected $_locale;

    public function __construct(Zend_Locale $_locale, Zend_Translate $_translate, array $_options = [])
    {
        $this->_locale = $_locale;
        $this->_translate = $_translate;

        if (isset($_options[self::TWIG_LOADER])) {
            $twigLoader = $_options[self::TWIG_LOADER];
        } else {
            $twigLoader = new Twig_Loader_Filesystem(['/'], dirname(__DIR__));
        }

        if (TINE20_BUILDTYPE === 'DEVELOPMENT' || (isset($_options[self::TWIG_CACHE]) && !$_options[self::TWIG_CACHE])) {
            $cacheDir = false;
        } else {
            $cacheDir = rtrim(Tinebase_Core::getCacheDir(), '/') . '/tine20Twig';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
        }

        $options = [
            'cache' => $cacheDir
        ];

        if (isset($_options[self::TWIG_AUTOESCAPE])) {
            $options['autoescape'] = $_options[self::TWIG_AUTOESCAPE];
        }
        $this->_twigEnvironment = new Twig_Environment($twigLoader, $options);
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUnusedParameterInspection */
        $this->_twigEnvironment->getExtension('core')->setEscaper('json', function($twigEnv, $string, $charset) {
            return json_encode($string);
        });

        $this->_addTwigFunctions();
    }

    /**
     * @param string $_filename
     * @return Twig_TemplateWrapper
     */
    public function load($_filename)
    {
        return $this->_twigEnvironment->load($_filename);
    }

    /**
     * adds twig function to the twig environment to be used in the templates
     */
    protected function _addTwigFunctions()
    {
        $locale = $this->_locale;
        $translate = $this->_translate;
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('translate',
            function ($str) use($locale, $translate) {
                return $translate->_($str, $locale);
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('_',
            function ($str) use($locale, $translate) {
                return $translate->_($str, $locale);
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('ngettext',
            function ($str) use($locale, $translate) {
                return $translate->plural($str, $locale);
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('dateFormat', function ($date, $format) {
            return Tinebase_Translation::dateToStringInTzAndLocaleFormat($date, null, null, $format);
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('relationTranslateModel', function ($model) {
            // TODO implement this!
            return $model;
        }));
    }
}
