<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * AreaLockState Model
 *
 * @package     Tinebase
 * @subpackage  Adapter
 *
 * @property expires
 * @property area
 */

class Tinebase_Model_AreaLockState extends Tinebase_Record_Abstract
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
        'recordName'        => 'Area Lock State',
        'recordsName'       => 'Area Lock States', // ngettext('Area Lock State', 'Area Lock States', n)
        'titleProperty'     => 'area',

        'appName'           => 'Tinebase',
        'modelName'         => 'AreaLockState',

        'fields'            => [
            'area' => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Area', // _('Area')
                'queryFilter'   => true
            ],
            'expires' => [
                // 2150-01-01 -> never
                // 1970-01-01 -> already expired
                'type'          => 'datetime',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Expires', // _('Expires')
            ],
        ]
    ];
}
