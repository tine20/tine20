<?php
/**
 * Tinebase Twig class
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
            $twigLoader = new Twig_Loader_Filesystem(['./'], dirname(__DIR__));
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

        $this->_twigEnvironment->addExtension(new Twig_Extensions_Extension_Intl());

        $this->_addTwigFunctions();

        $this->_addGlobals();
    }

    protected function _addGlobals()
    {
        $tbConfig = Tinebase_Config::getInstance();

        $globals = [
            'branding'          => [
                'logo'              => Tinebase_Core::getInstallLogo(),
                'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
                'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
                'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
            ],
            'user'              => [
                'locale'            => Tinebase_Core::getLocale(),
                'timezone'          => Tinebase_Core::getUserTimezone(),
            ],
            'currencySymbol'    => $tbConfig->{Tinebase_Config::CURRENCY_SYMBOL},
        ];
        $this->_twigEnvironment->addGlobal('app', $globals);
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
     * @param Twig_LoaderInterface $loader
     */
    public function addLoader(Twig_LoaderInterface $loader)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_twigEnvironment->getLoader()->addLoader($loader);
    }

    /**
     * @return Twig_Environment
     */
    public function getEnvironment()
    {
        return $this->_twigEnvironment;
    }

    /**
     * adds twig function to the twig environment to be used in the templates
     */
    protected function _addTwigFunctions()
    {
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('config', function ($key, $app='') {
            $config = Tinebase_Config::getInstance();
            if ($app) {
                $config = $config->{$app};
            }
            return $config->{$key};
        }));

        $locale = $this->_locale;
        $translate = $this->_translate;
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('translate',
            function ($str) use($locale, $translate) {
                $translatedStr = $translate->translate($str, $locale);
                if ($translatedStr == $str) {
                    $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $locale)->translate($str, $locale);
                }

                return $translatedStr;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('_',
            function ($str) use($locale, $translate) {
                $translatedStr = $translate->translate($str, $locale);
                if ($translatedStr == $str) {
                    $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $locale)->translate($str, $locale);
                }

                return $translatedStr;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('ngettext',
            function ($singular, $plural, $number) use($locale, $translate) {
                $translatedStr =  $translate->plural($singular, $plural, $number, $locale);
                if (in_array($translatedStr, [$singular, $plural])) {
                    $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $locale)->plural($singular, $plural, $number, $locale);
                }

                return $translatedStr;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('addNewLine',
            function ($str) {
                return (is_scalar($str) && strlen($str) > 0) ? $str . "\n" : $str;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('dateFormat', function ($date, $format) {
            if (!($date instanceof DateTime)) {
                $date = new Tinebase_DateTime($date, Tinebase_Core::getUserTimezone());
            }
            
            return Tinebase_Translation::dateToStringInTzAndLocaleFormat($date, null, null, $format);
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('relationTranslateModel', function ($model) {
            if (! class_exists($model)) return $model;
            return $model::getRecordName();
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('keyField', function ($appName, $keyFieldName, $key, $locale = null) {
            $config = Tinebase_Config::getAppConfig($appName)->$keyFieldName;
            $keyFieldRecord = ($config && $config->records instanceof Tinebase_Record_RecordSet && is_string($key))
                ? $config->records->getById($key)
                : false;

            if ($locale !== null) {
                $locale = Tinebase_Translation::getLocale($locale);
            }
            
            $translation = Tinebase_Translation::getTranslation($appName, $locale);
            return $keyFieldRecord ? $translation->translate($keyFieldRecord->value) : $key;
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('renderTags', function ($tags) {
            if (!($tags instanceof Tinebase_Record_RecordSet)) {
                return '';   
            }
            
            return implode(', ', $tags->getTitle());
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('findBySubProperty',
            function ($records, $property, $subProperty, $value) {
                return $records instanceof Tinebase_Record_RecordSet ?
                    $records->find(function($record) use($property, $subProperty, $value) {
                        return $record->{$property} instanceof Tinebase_Record_Interface &&
                            $record->{$property}->{$subProperty} === $value;
                }, null) : null;
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('filterBySubProperty',
            function ($records, $property, $subProperty, $value) {
                return $records instanceof Tinebase_Record_RecordSet ?
                    $records->filter(function($record) use($property, $subProperty, $value) {
                        return $record->{$property} instanceof Tinebase_Record_Interface &&
                            $record->{$property}->{$subProperty} === $value;
                    }, null) : null;
            }));
    }

    public function addExtension(Twig_ExtensionInterface $extension)
    {
        $this->_twigEnvironment->addExtension($extension);
    }
}
