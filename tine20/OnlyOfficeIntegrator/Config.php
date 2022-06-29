<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * OnlyOfficeIntegrator config class
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Config
 *
 */
class OnlyOfficeIntegrator_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    const APP_NAME = 'OnlyOfficeIntegrator';

    const ONLYOFFICE_PUBLIC_URL = 'onlyOfficePublicUrl';
    const ONLYOFFICE_SERVER_URL = 'onlyOfficeServerUrl';
    const TINE20_SERVER_URL = 'tine20ServerUrl';
    const JWT_ENABLED = 'jwtEnabled';
    const JWT_SECRET = 'jwtSecret';
    const TOKEN_LIVE_TIME = 'tokenLiveTime';
    const FORCE_SAVE_INTERVAL = 'forceSaveInterval';

    const FM_NODE_EDITING_CFNAME = 'ooi_editing';
    const FM_NODE_EDITORS_CFNAME = 'ooi_editors';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::ONLYOFFICE_PUBLIC_URL => [
            //_('Public OnlyOffice document server url')
            self::LABEL                 => 'Public OnlyOffice document server url',
            //_('OnlyOffice document server url accessible by the users browser')
            self::DESCRIPTION           => 'OnlyOffice document server url accessible by the users browser',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::ONLYOFFICE_SERVER_URL => [
            //_('Internal/Server OnlyOffice document server url')
            self::LABEL                 => 'Internal/Server OnlyOffice document server url',
            //_('OnlyOffice document server url accessible from this server. Leave empty if this is the same as the public url')
            self::DESCRIPTION           => 'OnlyOffice document server url accessible from this server. Leave empty if this is the same as the public url',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::TINE20_SERVER_URL => [
            //_('Server url of this installation')
            self::LABEL                 => 'Server url of this installation',
            //_('Server url of this installation accessible by the OnlyOffice document server')
            self::DESCRIPTION           => 'Server url of this installation accessible by the OnlyOffice document server',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::JWT_ENABLED => [
            //_('Enable oo jwt tokens')
            self::LABEL                 => 'Enable oo jwt tokens',
            //_('Enable tokens for browser requests corresponding to OnlyOffice JWT_ENABLED environment variable')
            self::DESCRIPTION           => 'Enable tokens for browser requests corresponding to OnlyOffice JWT_ENABLED environment variable',
            self::TYPE                  => self::TYPE_BOOL,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],

        self::JWT_SECRET => [
            //_('OnlyOffice jwt secret string')
            self::LABEL                 => 'OnlyOffice inbox secret string',
            //_('OnlyOffice jwt secret string corresponding to OnlyOffice JWT_SECRET environment variable')
            self::DESCRIPTION           => 'OnlyOffice jwt secret string corresponding to OnlyOffice JWT_SECRET environment variable',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::FORCE_SAVE_INTERVAL => [
            //_('Interval in seconds to force save')
            self::LABEL                 => 'Interval in seconds to force save',
            //_('Interval in seconds to force save')
            self::DESCRIPTION           => 'Interval in seconds to force save',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 0,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::TOKEN_LIVE_TIME => [
            //_('Live time of token subscriptions')
            self::LABEL                 => 'Live time of token subscriptions',
            //_('Live time of token subscriptions')
            self::DESCRIPTION           => 'Live time of token subscriptions',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 1200,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
    ];

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
