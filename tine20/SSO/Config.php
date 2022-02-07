<?php declare(strict_types=1);
/**
 * @package     SSO
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * SSO config class
 * 
 * @package     SSO
 * @subpackage  Config
 */
class SSO_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    public const APP_NAME = 'SSO';
    public const ENABLED = 'enabled';

    public const OAUTH2 = 'oauth2';
    public const OAUTH2_KEYS = 'keys';

    public const SAML2 = 'saml2';
    public const SAML2_ENTITYID = 'entityid';
    public const SAML2_KEYS = 'keys';
    public const SAML2_TINELOGOUT = 'tineLogout';


    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::OAUTH2                => [
            //_('Oauth2')
            self::LABEL                 => 'Oauth2',
            //_('Oauth2')
            self::DESCRIPTION           => 'Oauth2',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::ENABLED               => [
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false
                ],
                self::OAUTH2_KEYS           => [
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::DEFAULT_STR           => []
                ]
            ],
            self::DEFAULT_STR           => [],
        ],
        self::SAML2                 => [
            //_('SAML2')
            self::LABEL                 => 'SAML2',
            //_('SAML2')
            self::DESCRIPTION           => 'SAML2',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::ENABLED               => [
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false
                ],
                self::SAML2_ENTITYID        => [
                    self::TYPE                  => self::TYPE_STRING,
                    self::DEFAULT_STR           => 'tine20'
                ],
                self::SAML2_KEYS            => [
                    self::TYPE                  => self::TYPE_ARRAY,
                    self::DEFAULT_STR           => []
                ],
                self::SAML2_TINELOGOUT      => [
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ]
            ],
            self::DEFAULT_STR           => [],
        ]
    ];

    static function getProperties()
    {
        return self::$_properties;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;
}
