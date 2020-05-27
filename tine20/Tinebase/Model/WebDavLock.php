<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * WebDAV Lock Model
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */

class Tinebase_Model_WebDavLock extends Tinebase_Record_Abstract
{
    const TABLE_NAME = 'webdav_lock';

    const MODEL_NAME_PART = 'WebDavLock';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'version'           => 1,

        'appName'           => Tinebase_Config::APP_NAME,
        'modelName'         => self::MODEL_NAME_PART,
        'idProperty'        => 'token',

        'table'             => [
            'name'    => self::TABLE_NAME,
            'indexes' => [
                'timeout' => [
                    'columns' => ['timeout']
                ]
            ],
        ],

        'fields'            => [
            'token' => [
                'id'            => true,
                'type'          => 'string',
                'length'        => 255,
            ],
            'owner' => [
                'type'          => 'text'
            ],
            'uri' => [
                'type'          => 'text',
            ],
            'timeout' => [
                'type'          => 'integer',
            ],
            'created' => [
                'type'          => 'integer',
            ],
            'scope' => [
                'type'          => 'integer',
            ],
            'depth' => [
                'type'          => 'integer',
            ],
        ]
    ];
}
