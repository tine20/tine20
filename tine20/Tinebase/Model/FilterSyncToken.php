<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FilterSyncToken
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * FilterSyncToken Model
 *
 * @package     Tinebase
 * @subpackage  FilterSyncToken
 *
 * @property string                     filterHash
 * @property string                     filterSyncToken
 * @property array                      idLastModifiedMap
 * @property Tinebase_DateTime          created
 */

class Tinebase_Model_FilterSyncToken extends Tinebase_Record_Abstract
{
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
        'version'           => 2,
        'titleProperty'     => 'filterHash',

        'appName'           => 'Tinebase',
        'modelName'         => 'FilterSyncToken',

        'table'             => [
            'name'              => 'filter_sync_token',
            'indexes'           => [
                'created'           => [
                    'columns'           => ['created']
                ],
            ],
            'uniqueConstraints' => [
                'uniqueFilterHash'      => [
                    'columns'               => ['filterHash']
                ],
                'uniqueFilterSyncToken' => [
                    'columns'               => ['filterSyncToken']
                ],
            ],
        ],

        'fields'            => [
            'filterHash'        => [
                'type'              => 'string',
                'length'            => 40,
                'validators'        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
            ],
            'filterSyncToken'   => [
                'type'              => 'string',
                'length'            => 40,
                'validators'        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
            ],
            'idLastModifiedMap' => [
                'type'              => 'json',
                'validators'        => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => [],
                    Tinebase_Record_Validator_Json::class,
                ],
            ],
            'created'           => [
                'type'              => 'datetime',
            ]
        ]
    ];

    /**
     * Default constructor
     * Constructs an object and sets its record related properties.
     *
     * @todo what happens if not all properties in the datas are set?
     * The default values must also be set, even if no filtering is done!
     *
     * @param mixed $_data
     * @param bool $_bypassFilters sets {@see this->bypassFilters}
     * @param mixed $_convertDates sets {@see $this->convertDates} and optionaly {@see $this->$dateConversionFormat}
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        if (null === $_data) {
            $_data = ['created' => Tinebase_DateTime::now()];
        } elseif (!isset($_data['created'])) {
            $_data['created'] = Tinebase_DateTime::now();
        }

        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}