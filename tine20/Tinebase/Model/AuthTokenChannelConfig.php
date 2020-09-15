<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AuthTokenConfig Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                     name
 * @property array                      token_create_hook
 */

class Tinebase_Model_AuthTokenChannelConfig extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AuthTokenConfig';

    const FLDS_NAME = 'name';
    const FLDS_TOKEN_CREATE_HOOK = 'token_create_hook';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::FIELDS                => [
            self::NAME                      => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLDS_TOKEN_CREATE_HOOK    => [
                self::TYPE                      => self::TYPE_JSON,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
